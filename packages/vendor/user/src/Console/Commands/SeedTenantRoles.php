<?php

namespace Vendor\User\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class SeedTenantRoles extends Command
{
    protected $signature   = 'user:seed-roles {--guard=web : Le guard à utiliser}';
    protected $description = 'Crée les rôles Spatie par défaut (owner, admin, manager, user, viewer)';

    public function handle(): int
    {
        $guard = $this->option('guard');
        $roles = config('user.tenant_roles', []);

        $this->info('Création des rôles Spatie...');

        foreach (array_keys($roles) as $roleName) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => $guard]);
            $this->line("  ✓ Rôle <comment>{$roleName}</comment> " . ($role->wasRecentlyCreated ? 'créé' : 'existant'));
        }

        // Permissions de base par module
        $permissions = [
            // Clients
            'clients.read', 'clients.create', 'clients.update', 'clients.delete',
            // Factures
            'invoices.read', 'invoices.create', 'invoices.update', 'invoices.delete',
            // Stock
            'stock.read', 'stock.create', 'stock.update', 'stock.delete',
            // Utilisateurs
            'users.read', 'users.invite', 'users.update', 'users.delete',
            // Rapports
            'reports.read',
            // Paramètres
            'settings.read', 'settings.update',
        ];

        $this->info('Création des permissions...');
        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => $guard]);
            $this->line("  ✓ Permission <comment>{$perm}</comment>");
        }

        // Assigner les permissions aux rôles
        $rolePerms = [
            'owner'   => $permissions,
            'admin'   => array_filter($permissions, fn($p) => !str_starts_with($p, 'settings')),
            'manager' => ['clients.read','clients.create','clients.update','invoices.read','invoices.create','invoices.update','stock.read','reports.read'],
            'user'    => ['clients.read','clients.create','invoices.read'],
            'viewer'  => ['clients.read','invoices.read','stock.read','reports.read'],
        ];

        $this->info('Attribution des permissions aux rôles...');
        foreach ($rolePerms as $roleName => $perms) {
            $role = Role::where('name', $roleName)->where('guard_name', $guard)->first();
            if ($role) {
                $role->syncPermissions($perms);
                $this->line("  ✓ Rôle <comment>{$roleName}</comment> : " . count($perms) . " permissions");
            }
        }

        $this->info('✅ Rôles et permissions créés avec succès.');

        return self::SUCCESS;
    }
}