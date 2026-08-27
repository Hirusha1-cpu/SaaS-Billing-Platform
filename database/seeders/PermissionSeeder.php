<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Log;

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
            try {
                Permission::firstOrCreate([
                    'name' => $permission,
                    'guard_name' => 'web'
                ]);
            } catch (\Exception $e) {
                Log::error('Permission creation failed: ' . $e->getMessage());
            }
        }

        // Create roles and assign permissions
        try {
            // Admin Role - හැම දේම පුළුවන්
            $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
            $admin->syncPermissions(Permission::all());

            // Accountant Role - Invoice create/edit කරන්න පුළුවන්, delete නෑ
            $accountant = Role::firstOrCreate(['name' => 'accountant', 'guard_name' => 'web']);
            $accountant->syncPermissions([
                'view invoices',
                'create invoices',
                'edit invoices',
                'send invoices',
                'mark paid invoices',
                'view users',
                'view reports',
            ]);

            // Viewer Role - බලන්න විතරයි පුළුවන්
            $viewer = Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'web']);
            $viewer->syncPermissions([
                'view invoices',
                'view reports',
            ]);

            // Assign roles to existing users
            $users = User::all();
            foreach ($users as $user) {
                try {
                    $user->assignRole($user->role ?? 'viewer');
                } catch (\Exception $e) {
                    Log::error('Role assignment failed for user ' . $user->id . ': ' . $e->getMessage());
                }
            }

            Log::info('Permissions and roles seeded successfully');

        } catch (\Exception $e) {
            Log::error('Role creation failed: ' . $e->getMessage());
        }
    }
}