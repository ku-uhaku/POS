<?php

use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    // Create roles and permissions with sanctum guard
    $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum']);
    $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'sanctum']);

    $viewStoresPermission = Permission::firstOrCreate(['name' => 'view stores', 'guard_name' => 'sanctum']);
    $createStoresPermission = Permission::firstOrCreate(['name' => 'create stores', 'guard_name' => 'sanctum']);
    $editStoresPermission = Permission::firstOrCreate(['name' => 'edit stores', 'guard_name' => 'sanctum']);
    $deleteStoresPermission = Permission::firstOrCreate(['name' => 'delete stores', 'guard_name' => 'sanctum']);

    $adminRole->syncPermissions([$viewStoresPermission, $createStoresPermission, $editStoresPermission, $deleteStoresPermission]);
    $userRole->syncPermissions([$viewStoresPermission]);

    // Create test users
    $this->admin = User::factory()->create([
        'email' => 'admin@storetest.com',
        'password' => Hash::make('password'),
    ]);
    $this->admin->assignRole('admin');

    $this->regularUser = User::factory()->create([
        'email' => 'user@storetest.com',
        'password' => Hash::make('password'),
    ]);
    $this->regularUser->assignRole('user');

    $this->userWithoutPermission = User::factory()->create([
        'email' => 'noperm@storetest.com',
        'password' => Hash::make('password'),
    ]);
});

test('admin can view stores list', function () {
    Store::factory()->count(3)->create();
    $token = $this->admin->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/stores');

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
                        'status',
                        'users_count',
                    ],
                ],
                'pagination',
            ],
        ])
        ->assertJson([
            'success' => true,
            'message' => 'Stores retrieved successfully',
        ]);
});

test('user with view stores permission can view stores list', function () {
    Store::factory()->count(2)->create();
    $token = $this->regularUser->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/stores');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Stores retrieved successfully',
        ]);
});

test('user without permission cannot view stores list', function () {
    $token = $this->userWithoutPermission->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/stores');

    $response->assertStatus(403)
        ->assertJson([
            'success' => false,
            'message' => 'You do not have the required permission to perform this action.',
        ]);
});

test('admin can create stores', function () {
    $token = $this->admin->createToken('test-token')->plainTextToken;
    $storeData = [
        'name' => 'Test Store',
        'code' => 'STORE-001',
        'address' => '123 Main St',
        'city' => 'New York',
        'state' => 'NY',
        'country' => 'USA',
        'postal_code' => '10001',
        'phone' => '+1234567890',
        'email' => 'store@example.com',
        'status' => 'active',
    ];

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/stores', $storeData);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'store' => [
                    'id',
                    'name',
                    'code',
                    'address',
                    'city',
                    'status',
                ],
            ],
        ])
        ->assertJson([
            'success' => true,
            'message' => 'Store created successfully',
            'data' => [
                'store' => [
                    'name' => 'Test Store',
                    'code' => 'STORE-001',
                    'status' => 'active',
                ],
            ],
        ]);

    $this->assertDatabaseHas('stores', [
        'name' => 'Test Store',
        'code' => 'STORE-001',
        'status' => 'active',
    ]);
});

test('user without create permission cannot create stores', function () {
    $token = $this->regularUser->createToken('test-token')->plainTextToken;
    $storeData = [
        'name' => 'Unauthorized Store',
        'code' => 'STORE-002',
    ];

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/stores', $storeData);

    $response->assertStatus(403)
        ->assertJson([
            'success' => false,
            'message' => 'You do not have the required permission to perform this action.',
        ]);

    $this->assertDatabaseMissing('stores', ['code' => 'STORE-002']);
});

test('admin can view a specific store', function () {
    $store = Store::factory()->create();
    $token = $this->admin->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/v1/stores/{$store->id}");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'store' => [
                    'id',
                    'name',
                    'code',
                    'address',
                    'city',
                    'state',
                    'country',
                    'postal_code',
                    'phone',
                    'email',
                    'status',
                ],
            ],
        ])
        ->assertJson([
            'success' => true,
            'message' => 'Store retrieved successfully',
            'data' => [
                'store' => [
                    'id' => $store->id,
                    'name' => $store->name,
                ],
            ],
        ]);
});

test('admin can update stores', function () {
    $store = Store::factory()->create(['name' => 'Old Store Name']);
    $token = $this->admin->createToken('test-token')->plainTextToken;

    $updateData = [
        'name' => 'Updated Store Name',
        'status' => 'inactive',
    ];

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/v1/stores/{$store->id}", $updateData);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Store updated successfully',
            'data' => [
                'store' => [
                    'name' => 'Updated Store Name',
                    'status' => 'inactive',
                ],
            ],
        ]);

    $this->assertDatabaseHas('stores', [
        'id' => $store->id,
        'name' => 'Updated Store Name',
        'status' => 'inactive',
    ]);
});

test('user without edit permission cannot update stores', function () {
    $store = Store::factory()->create();
    $token = $this->regularUser->createToken('test-token')->plainTextToken;

    $updateData = [
        'name' => 'Unauthorized Update',
    ];

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/v1/stores/{$store->id}", $updateData);

    $response->assertStatus(403)
        ->assertJson([
            'success' => false,
            'message' => 'You do not have the required permission to perform this action.',
        ]);
});

test('admin can delete stores', function () {
    $store = Store::factory()->create();
    $token = $this->admin->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson("/api/v1/stores/{$store->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Store deleted successfully',
        ]);

    $this->assertSoftDeleted('stores', ['id' => $store->id]);
});

test('user without delete permission cannot delete stores', function () {
    $store = Store::factory()->create();
    $token = $this->regularUser->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson("/api/v1/stores/{$store->id}");

    $response->assertStatus(403)
        ->assertJson([
            'success' => false,
            'message' => 'You do not have the required permission to perform this action.',
        ]);

    $this->assertDatabaseHas('stores', ['id' => $store->id, 'deleted_at' => null]);
});

test('store creation requires name field', function () {
    $token = $this->admin->createToken('test-token')->plainTextToken;
    $storeData = [
        'code' => 'STORE-003',
    ];

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/stores', $storeData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

test('store code must be unique', function () {
    Store::factory()->create(['code' => 'STORE-UNIQUE']);
    $token = $this->admin->createToken('test-token')->plainTextToken;
    $storeData = [
        'name' => 'Duplicate Store',
        'code' => 'STORE-UNIQUE',
    ];

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/stores', $storeData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['code']);
});

test('store status must be valid enum value', function () {
    $token = $this->admin->createToken('test-token')->plainTextToken;
    $storeData = [
        'name' => 'Invalid Status Store',
        'status' => 'invalid_status',
    ];

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/stores', $storeData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['status']);
});

test('unauthenticated user cannot access store endpoints', function () {
    $response = $this->getJson('/api/v1/stores');
    $response->assertStatus(401)
        ->assertJson([
            'success' => false,
            'message' => 'Unauthenticated',
        ]);
});

test('store resource includes users when loaded', function () {
    $store = Store::factory()->create();
    User::factory()->count(2)->create(['store_id' => $store->id]);
    $token = $this->admin->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/v1/stores/{$store->id}");

    $response->assertStatus(200);
    $storeData = $response->json('data.store');
    expect($storeData['users'])->toBeArray();
    expect($storeData['users'])->toHaveCount(2);
});
