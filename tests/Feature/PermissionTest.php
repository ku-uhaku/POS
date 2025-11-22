<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    // Create roles and permissions with sanctum guard
    $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum']);
    $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'sanctum']);

    $viewUsersPermission = Permission::firstOrCreate(['name' => 'view users', 'guard_name' => 'sanctum']);
    $deleteUsersPermission = Permission::firstOrCreate(['name' => 'delete users', 'guard_name' => 'sanctum']);
    $viewRolesPermission = Permission::firstOrCreate(['name' => 'view roles', 'guard_name' => 'sanctum']);
    $createRolesPermission = Permission::firstOrCreate(['name' => 'create roles', 'guard_name' => 'sanctum']);
    $editRolesPermission = Permission::firstOrCreate(['name' => 'edit roles', 'guard_name' => 'sanctum']);
    $deleteRolesPermission = Permission::firstOrCreate(['name' => 'delete roles', 'guard_name' => 'sanctum']);

    $adminRole->syncPermissions([$viewUsersPermission, $deleteUsersPermission, $viewRolesPermission, $createRolesPermission, $editRolesPermission, $deleteRolesPermission]);
    $userRole->syncPermissions([$viewUsersPermission]);

    // Create test users
    $this->admin = User::factory()->create([
        'email' => 'admin@test.com',
        'password' => Hash::make('password'),
    ]);
    $this->admin->assignRole('admin');

    $this->regularUser = User::factory()->create([
        'email' => 'user@test.com',
        'password' => Hash::make('password'),
    ]);
    $this->regularUser->assignRole('user');

    $this->userWithoutRole = User::factory()->create([
        'email' => 'norole@test.com',
        'password' => Hash::make('password'),
    ]);
});

test('admin can view users list', function () {
    $token = $this->admin->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/users');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'users',
                'pagination',
            ],
        ])
        ->assertJson([
            'success' => true,
        ]);
});

test('user with view users permission can view users list', function () {
    $token = $this->regularUser->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/users');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);
});

test('user without permission cannot view users list', function () {
    $userWithoutPermission = User::factory()->create();
    $token = $userWithoutPermission->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/users');

    $response->assertStatus(403)
        ->assertJson([
            'success' => false,
            'message' => 'You do not have the required permission to perform this action.',
        ]);
});

test('admin can delete users', function () {
    $userToDelete = User::factory()->create();
    $token = $this->admin->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson("/api/v1/users/{$userToDelete->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'User deleted successfully',
        ]);

    $this->assertDatabaseMissing('users', ['id' => $userToDelete->id]);
});

test('user without delete permission cannot delete users', function () {
    $userToDelete = User::factory()->create();
    $token = $this->regularUser->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson("/api/v1/users/{$userToDelete->id}");

    $response->assertStatus(403)
        ->assertJson([
            'success' => false,
        ]);

    $this->assertDatabaseHas('users', ['id' => $userToDelete->id]);
});

test('admin can view roles', function () {
    $token = $this->admin->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/roles');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'roles' => [
                    '*' => [
                        'id',
                        'name',
                        'permissions',
                    ],
                ],
            ],
        ])
        ->assertJson([
            'success' => true,
        ]);
});

test('admin can create roles', function () {
    $token = $this->admin->createToken('test-token')->plainTextToken;

    $roleData = [
        'name' => 'manager',
        'permissions' => ['view users', 'view roles'],
    ];

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/roles', $roleData);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'role' => [
                    'id',
                    'name',
                    'permissions',
                ],
            ],
        ])
        ->assertJson([
            'success' => true,
            'message' => 'Role created successfully',
            'data' => [
                'role' => [
                    'name' => 'manager',
                ],
            ],
        ]);

    $this->assertDatabaseHas('roles', ['name' => 'manager']);
});

test('user without create roles permission cannot create roles', function () {
    $token = $this->regularUser->createToken('test-token')->plainTextToken;

    $roleData = [
        'name' => 'manager',
    ];

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/roles', $roleData);

    $response->assertStatus(403)
        ->assertJson([
            'success' => false,
        ]);
});

test('admin can update roles', function () {
    $role = Role::create(['name' => 'test-role', 'guard_name' => 'sanctum']);
    $token = $this->admin->createToken('test-token')->plainTextToken;

    $updateData = [
        'name' => 'updated-role',
        'permissions' => ['view users'],
    ];

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/v1/roles/{$role->id}", $updateData);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Role updated successfully',
            'data' => [
                'role' => [
                    'name' => 'updated-role',
                ],
            ],
        ]);

    $this->assertDatabaseHas('roles', ['name' => 'updated-role']);
    $this->assertDatabaseMissing('roles', ['name' => 'test-role']);
});

test('admin can delete roles', function () {
    $role = Role::create(['name' => 'role-to-delete', 'guard_name' => 'sanctum']);
    $token = $this->admin->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson("/api/v1/roles/{$role->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Role deleted successfully',
        ]);

    $this->assertDatabaseMissing('roles', ['id' => $role->id]);
});

test('unauthenticated user cannot access protected routes', function () {
    $response = $this->getJson('/api/v1/users');

    $response->assertStatus(401)
        ->assertJson([
            'success' => false,
            'message' => 'Unauthenticated',
        ]);
});

test('user resource includes roles when loaded', function () {
    $token = $this->admin->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/users');

    $response->assertStatus(200);
    
    $users = $response->json('data.users');
    expect($users)->toBeArray();
    
    if (count($users) > 0) {
        expect($users[0])->toHaveKey('roles');
    }
});

test('user can check their own permissions', function () {
    $token = $this->admin->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/auth/profile');

    $response->assertStatus(200);
    
    // Admin should have permissions
    expect($this->admin->can('view users'))->toBeTrue();
    expect($this->admin->can('delete users'))->toBeTrue();
});

test('regular user has limited permissions', function () {
    expect($this->regularUser->can('view users'))->toBeTrue();
    expect($this->regularUser->can('delete users'))->toBeFalse();
    expect($this->regularUser->can('create roles'))->toBeFalse();
});

test('user without role has no permissions', function () {
    expect($this->userWithoutRole->can('view users'))->toBeFalse();
    expect($this->userWithoutRole->can('delete users'))->toBeFalse();
});

