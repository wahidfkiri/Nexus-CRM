<?php

namespace Vendor\Rbac\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class SeedRbacCommand extends Command
{
    protected $signature   = 'rbac:seed
                              {--tenant= : ID du tenant (tous si omis)}
                              {--guard=web : Guard Spatie}
                              {--reset : Supprimer et recréer les permissions}';
    protected $description = 'Crée les rôles, permissions et les associe selon la config rbac.php';

    public function handle(): int
    {
        $guard = $this->option('guard');

        // ── 1. Créer toutes les permissions globales (sans tenant) ──────────
        $this->info('📦 Création des permissions...');
        $groups = config('rbac.permission_groups', []);

        if ($this->option('reset')) {
            Permission::where('guard_name', $guard)->whereNull('tenant_id')->delete();
            $this->warn('  ↺ Permissions existantes supprimées.');
        }

        $allPerms = [];
        foreach ($groups as $groupKey => $groupDef) {
            foreach ($groupDef['permissions'] as $name => $label) {
                $perm = Permission::firstOrCreate(
                    ['name' => $name, 'guard_name' => $guard],
                    ['label' => $label, 'group' => $groupKey, 'tenant_id' => null]
                );
                $allPerms[$name] = $perm;
                $this->line("  ✓ <comment>{$name}</comment>" . ($perm->wasRecentlyCreated ? ' (créée)' : ''));
            }
        }

        // ── 2. Créer les rôles système (sans tenant) ────────────────────────
        $this->info('🛡  Création des rôles système...');
        $defaultRoles   = config('rbac.default_roles', []);
        $rolePermsMap   = config('rbac.default_role_permissions', []);

        foreach ($defaultRoles as $roleName => $roleDef) {
            $role = Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => $guard, 'tenant_id' => null],
                [
                    'label'       => $roleDef['label'],
                    'color'       => $roleDef['color'],
                    'description' => $roleDef['description'],
                    'is_system'   => in_array($roleName, config('rbac.system_roles', ['owner','super-admin'])),
                    'is_active'   => true,
                ]
            );

            $this->line("  ✓ Rôle <comment>{$roleName}</comment> " . ($role->wasRecentlyCreated ? '(créé)' : '(existant)'));

            // Assigner les permissions
            $permsToAssign = $rolePermsMap[$roleName] ?? [];
            if ($permsToAssign === ['*']) {
                $role->syncPermissions(array_keys($allPerms));
                $this->line("    → Toutes les permissions assignées");
            } else {
                $validPerms = array_intersect($permsToAssign, array_keys($allPerms));
                $role->syncPermissions($validPerms);
                $this->line("    → " . count($validPerms) . " permission(s) assignée(s)");
            }
        }

        // ── 3. Créer les rôles pour chaque tenant ───────────────────────────
        $tenantId = $this->option('tenant');

        if ($tenantId) {
            $tenants = \Vendor\CrmCore\Models\Tenant::where('id', $tenantId)->get();
        } else {
            $tenants = \Vendor\CrmCore\Models\Tenant::all();
        }

        if ($tenants->isNotEmpty()) {
            $this->info("🏢 Application aux tenants ({$tenants->count()})...");
            foreach ($tenants as $tenant) {
                $this->line("  Tenant #{$tenant->id} : {$tenant->name}");

                // Assigner le rôle owner au tenant owner
                $owner = User::where('tenant_id', $tenant->id)
                    ->where('is_tenant_owner', true)
                    ->first();

                if ($owner) {
                    $ownerRole = Role::where('name', 'owner')->where('guard_name', $guard)->first();
                    if ($ownerRole) {
                        $owner->syncRoles(['owner']);
                        $this->line("    ✓ Owner <comment>{$owner->name}</comment> → rôle owner assigné");
                    }
                }
            }
        }

        // ── 4. Vider le cache Spatie ────────────────────────────────────────
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->newLine();
        $this->info('✅ RBAC initialisé avec succès.');
        $this->table(
            ['Élément', 'Total'],
            [
                ['Permissions', count($allPerms)],
                ['Rôles système', count($defaultRoles)],
                ['Tenants traités', $tenants->count()],
            ]
        );

        return self::SUCCESS;
    }
}