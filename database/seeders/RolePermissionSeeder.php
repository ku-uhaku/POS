<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // User management permissions
            'view users',
            'create users',
            'edit users',
            'delete users',

            // Role management permissions
            'view roles',
            'create roles',
            'edit roles',
            'delete roles',

            // Permission management permissions
            'view permissions',
            'create permissions',
            'edit permissions',
            'delete permissions',

            // Product management permissions (example)
            'view products',
            'create products',
            'edit products',
            'delete products',

            // Order management permissions (example)
            'view orders',
            'create orders',
            'edit orders',
            'delete orders',

            // Store management permissions
            'view stores',
            'create stores',
            'edit stores',
            'delete stores',
        ];

        // Delete existing permissions and roles with wrong guard
        Permission::where('guard_name', '!=', 'sanctum')->delete();
        Role::where('guard_name', '!=', 'sanctum')->delete();

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'sanctum']
            );
        }

        // Create roles
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum']);
        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'sanctum']);

        // Get all permissions for sanctum guard
        $allPermissions = Permission::where('guard_name', 'sanctum')->get();

        // Assign all permissions to admin role
        $adminRole->syncPermissions($allPermissions);

        // Assign basic permissions to user role
        $userRole->syncPermissions(
            Permission::where('guard_name', 'sanctum')
                ->whereIn('name', ['view products', 'view orders', 'create orders'])
                ->get()
        );

        $this->command->info('Roles and permissions seeded successfully!');
        $this->command->info('Admin role has all permissions');
        $this->command->info('User role has basic permissions (view products, view orders, create orders)');
    }
}
