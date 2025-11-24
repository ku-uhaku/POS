<?php

use App\Models\Contact;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->storeA = Store::factory()->create(['name' => 'Store A']);
    $this->storeB = Store::factory()->create(['name' => 'Store B']);

    $this->viewContactsPermission = Permission::firstOrCreate([
        'name' => 'view contacts',
        'guard_name' => 'sanctum',
    ]);

    $this->viewer = User::factory()->create([
        'email' => 'viewer@example.com',
        'password' => Hash::make('password'),
        'store_id' => $this->storeA->id,
        'default_store_id' => $this->storeA->id,
    ]);
    $this->viewer->givePermissionTo($this->viewContactsPermission);
    $this->viewer->stores()->sync([$this->storeA->id]);

    $this->noAccessUser = User::factory()->create([
        'email' => 'noaccess@example.com',
        'password' => Hash::make('password'),
    ]);
});

it('returns contacts for stores the user can access', function () {
    Contact::factory()->create([
        'store_id' => $this->storeA->id,
        'contact_name' => 'Alice Client',
        'type' => 'client',
        'client_type' => 'individual',
    ]);

    Contact::factory()->create([
        'store_id' => $this->storeB->id,
        'contact_name' => 'Bob Supplier',
        'type' => 'supplier',
        'client_type' => 'company',
    ]);

    Sanctum::actingAs($this->viewer, [], 'sanctum');

    $response = $this->getJson('/api/v1/contacts');

    $response->assertSuccessful();
    $data = $response->json('data.contacts');
    expect($data)->toHaveCount(1);
    expect($data[0]['contact_name'])->toBe('Alice Client');
});

it('applies filtering parameters when provided', function () {
    Contact::factory()->create([
        'store_id' => $this->storeA->id,
        'contact_name' => 'Acme Supplier',
        'company_name' => 'Acme Corp',
        'type' => 'supplier',
        'client_type' => 'company',
    ]);

    Contact::factory()->create([
        'store_id' => $this->storeA->id,
        'contact_name' => 'Beta Client',
        'company_name' => 'Beta LLC',
        'type' => 'client',
        'client_type' => 'individual',
    ]);

    Sanctum::actingAs($this->viewer, [], 'sanctum');

    $response = $this->getJson('/api/v1/contacts?type=supplier&client_type=company&search=Acme&per_page=1');

    $response->assertSuccessful()
        ->assertJsonPath('data.pagination.per_page', 1)
        ->assertJsonPath('data.pagination.total', 1);

    $data = $response->json('data.contacts');
    expect($data)->toHaveCount(1);
    expect($data[0]['company_name'])->toBe('Acme Corp');
});

it('prevents users without permission from listing contacts', function () {
    Sanctum::actingAs($this->noAccessUser, [], 'sanctum');

    $response = $this->getJson('/api/v1/contacts');

    $response->assertForbidden()
        ->assertJson([
            'success' => false,
            'message' => 'You do not have permission to view contacts.',
        ]);
});

it('prevents accessing contacts for unauthorized stores', function () {
    $otherStore = Store::factory()->create();
    Contact::factory()->create([
        'store_id' => $otherStore->id,
        'contact_name' => 'Gamma Supplier',
        'type' => 'supplier',
        'client_type' => 'company',
    ]);

    Sanctum::actingAs($this->viewer, [], 'sanctum');

    $response = $this->getJson("/api/v1/contacts?store_id={$otherStore->id}");

    $response->assertForbidden()
        ->assertJson([
            'success' => false,
            'message' => 'You do not have access to this store.',
        ]);
});
