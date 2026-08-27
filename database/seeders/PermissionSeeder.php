<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            'view invoices',
            'create invoices',
            'edit invoices',
            'delete invoices',
            'send invoices',
            'mark paid invoices',
            'view users',
            'create users',
            'edit users',
            'delete users',
            'view reports',
            'manage settings',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create roles and assign permissions
        $admin = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo(Permission::all());

        $accountant = Role::create(['name' => 'accountant', 'guard_name' => 'web']);
        $accountant->givePermissionTo([
            'view invoices',
            'create invoices',
            'edit invoices',
            'send invoices',
            'mark paid invoices',
            'view users',
            'view reports',
        ]);

        $viewer = Role::create(['name' => 'viewer', 'guard_name' => 'web']);
        $viewer->givePermissionTo([
            'view invoices',
            'view reports',
        ]);

        // Create admin user (if needed)
        $user = \App\Models\User::first();
        if ($user) {
            $user->assignRole('admin');
        }
    }
}