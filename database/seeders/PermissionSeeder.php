<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Permissions Clients
        $permissions = [
            'view_clients',
            'create_clients',
            'edit_clients',
            'delete_clients',
            'export_clients',
            
            // Permissions Invoices
            'view_invoices',
            'create_invoices',
            'edit_invoices',
            'delete_invoices',
            
            // Permissions Dashboard
            'view_dashboard',
            
            // Permissions Settings
            'view_settings',
            'edit_settings',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Créer les rôles
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdmin->givePermissionTo(Permission::all());

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo([
            'view_dashboard',
            'view_clients', 'create_clients', 'edit_clients', 'delete_clients', 'export_clients',
            'view_invoices', 'create_invoices', 'edit_invoices', 'delete_invoices',
        ]);

        $user = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        $user->givePermissionTo([
            'view_dashboard',
            'view_clients', 'create_clients',
            'view_invoices',
        ]);
    }
}