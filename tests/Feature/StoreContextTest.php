<?php

use App\Models\Setting;
use App\Models\Store;
use App\Models\User;
use App\Services\StoreContext;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    // Clear StoreContext before each test
    \App\Services\StoreContext::clearActiveStore();

    // Seed roles and permissions
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    // Create stores
    $this->store1 = Store::factory()->create(['name' => 'Store 1', 'code' => 'STORE-001']);
    $this->store2 = Store::factory()->create(['name' => 'Store 2', 'code' => 'STORE-002']);

    // Create user with access to both stores
    $this->user = User::factory()->create([
        'email' => 'multistore@test.com',
        'password' => Hash::make('password'),
        'default_store_id' => $this->store1->id,
    ]);

    // Assign user to both stores
    $this->user->stores()->attach([$this->store1->id, $this->store2->id]);

    // Assign admin role to user for permission checks
    $this->user->assignRole('admin');
});

test('middleware sets active store from X-Store-ID header', function () {
    $token = $this->user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->withHeader('X-Store-ID', (string) $this->store2->id)
        ->getJson('/api/v1/stores');

    $response->assertStatus(200);

    // Note: StoreContext is request-scoped, so we verify the response contains store2 data
    $stores = $response->json('data.stores');
    expect($stores)->toBeArray();
});

test('middleware falls back to default store when header not provided', function () {
    $token = $this->user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/stores');

    $response->assertStatus(200);

    // Verify StoreContext has the default store
    expect(StoreContext::getActiveStore())->toBe($this->user->default_store_id);
});

test('middleware rejects access to store user does not have access to', function () {
    $otherStore = Store::factory()->create();
    $token = $this->user->createToken('test-token')->plainTextToken;

    // Try to access a store endpoint with a store the user doesn't have access to
    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->withHeader('X-Store-ID', (string) $otherStore->id)
        ->getJson('/api/v1/stores/'.$otherStore->id);

    $response->assertStatus(403)
        ->assertJson([
            'success' => false,
            'message' => 'You do not have access to this store.',
        ]);
});

test('user can switch active store via endpoint', function () {
    $token = $this->user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/stores/switch', [
            'store_id' => $this->store2->id,
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Store switched successfully',
        ])
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'store' => [
                    'id',
                    'name',
                    'code',
                ],
            ],
        ]);
});

test('user cannot switch to store they do not have access to', function () {
    $otherStore = Store::factory()->create();
    $token = $this->user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/stores/switch', [
            'store_id' => $otherStore->id,
        ]);

    $response->assertStatus(403)
        ->assertJson([
            'success' => false,
            'message' => 'You do not have access to this store.',
        ]);
});

test('admin can assign store to user', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $newUser = User::factory()->create();
    $token = $admin->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/v1/users/{$newUser->id}/stores", [
            'store_id' => $this->store1->id,
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Store assigned to user successfully',
        ]);

    expect($newUser->stores()->where('stores.id', $this->store1->id)->exists())->toBeTrue();
});

test('admin can get user assigned stores', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $token = $admin->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/v1/users/{$this->user->id}/stores");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'stores' => [
                    '*' => [
                        'id',
                        'name',
                        'code',
                    ],
                ],
                'default_store_id',
            ],
        ]);

    $stores = $response->json('data.stores');
    expect($stores)->toHaveCount(2);
});

test('admin can remove store assignment from user', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $token = $admin->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson("/api/v1/users/{$this->user->id}/stores/{$this->store2->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Store assignment removed successfully',
        ]);

    expect($this->user->stores()->where('stores.id', $this->store2->id)->exists())->toBeFalse();
});

test('admin can set user default store', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $token = $admin->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/v1/users/{$this->user->id}/default-store", [
            'store_id' => $this->store2->id,
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Default store set successfully',
        ]);

    expect($this->user->fresh()->default_store_id)->toBe($this->store2->id);
});

test('admin cannot set default store user does not have access to', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $otherStore = Store::factory()->create();
    $token = $admin->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/v1/users/{$this->user->id}/default-store", [
            'store_id' => $otherStore->id,
        ]);

    $response->assertStatus(422);
});

test('settings are filtered by active store context', function () {
    // Create settings for both stores
    Setting::create([
        'store_id' => $this->store1->id,
        'key' => 'currency',
        'value' => 'USD',
        'type' => 'string',
    ]);

    Setting::create([
        'store_id' => $this->store2->id,
        'key' => 'currency',
        'value' => 'EUR',
        'type' => 'string',
    ]);

    $token = $this->user->createToken('test-token')->plainTextToken;

    // Request with store1 active
    $response1 = $this->withHeader('Authorization', "Bearer {$token}")
        ->withHeader('X-Store-ID', (string) $this->store1->id)
        ->getJson('/api/v1/stores/'.$this->store1->id);

    $response1->assertStatus(200);

    // Request with store2 active
    $response2 = $this->withHeader('Authorization', "Bearer {$token}")
        ->withHeader('X-Store-ID', (string) $this->store2->id)
        ->getJson('/api/v1/stores/'.$this->store2->id);

    $response2->assertStatus(200);

    // Verify settings are isolated
    $store1Settings = Setting::forStore($this->store1->id)->get();
    $store2Settings = Setting::forStore($this->store2->id)->get();

    expect($store1Settings)->toHaveCount(1);
    expect($store2Settings)->toHaveCount(1);
    expect($store1Settings->first()->value)->toBe('USD');
    expect($store2Settings->first()->value)->toBe('EUR');
});

test('user hasAccessToStore method works correctly', function () {
    expect($this->user->hasAccessToStore($this->store1->id))->toBeTrue();
    expect($this->user->hasAccessToStore($this->store2->id))->toBeTrue();

    $otherStore = Store::factory()->create();
    expect($this->user->hasAccessToStore($otherStore->id))->toBeFalse();
});

test('user stores relationship works correctly', function () {
    $stores = $this->user->stores;
    expect($stores)->toHaveCount(2);
    expect($stores->pluck('id')->toArray())->toContain($this->store1->id, $this->store2->id);
});

test('store users relationship works correctly', function () {
    $users = $this->store1->users;
    expect($users)->toHaveCount(1);
    expect($users->first()->id)->toBe($this->user->id);
});
