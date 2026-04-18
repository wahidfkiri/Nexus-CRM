<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Vendor\CrmCore\Models\Tenant;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'phone',
        'position',
        'company',
        'bio',
        'is_active',
        'tenant_id',
        'role_in_tenant',
        'is_tenant_owner',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'is_tenant_owner' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    // ==================== RELATIONS ====================
    
    /**
     * Relation avec le tenant (entreprise)
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Clients créés par cet utilisateur
     */
    public function clients()
    {
        return $this->hasMany('Vendor\Client\Models\Client', 'user_id');
    }

    /**
     * Clients assignés à cet utilisateur
     */
    public function assignedClients()
    {
        return $this->hasMany('Vendor\Client\Models\Client', 'assigned_to');
    }

    // ==================== ACCESSORS ====================
    
    /**
     * Récupérer le nom complet
     */
    public function getFullNameAttribute(): string
    {        
        return $this->name ?? $this->email;
    }

    /**
     * Récupérer les initiales (2 premières lettres)
     */
    public function getInitialsAttribute(): string
    {
        
        
        // Méthode 2: À partir du nom complet
        if ($this->name) {
            $parts = explode(' ', $this->name);
            if (count($parts) >= 2) {
                return strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
            }
            return strtoupper(substr($this->name, 0, 2));
        }
        
        // Méthode 3: À partir de l'email
        if ($this->email) {
            return strtoupper(substr($this->email, 0, 2));
        }
        
        return 'JD';
    }

    /**
     * Récupérer le rôle dans le tenant (libellé)
     */
    public function getRoleInTenantLabelAttribute(): string
    {
        $roles = [
            'owner' => 'Propriétaire',
            'admin' => 'Administrateur',
            'manager' => 'Gestionnaire',
            'user' => 'Utilisateur',
        ];
        
        return $roles[$this->role_in_tenant] ?? $this->role_in_tenant;
    }

    /**
     * Vérifier si l'utilisateur est le propriétaire du tenant
     */
    public function getIsOwnerOfTenantAttribute(): bool
    {
        return $this->is_tenant_owner === true || $this->role_in_tenant === 'owner';
    }

    // ==================== SCOPES ====================
    
    /**
     * Scope pour les utilisateurs actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pour les utilisateurs inactifs
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope pour les utilisateurs d'un tenant spécifique
     */
    public function scopeByTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope pour les propriétaires de tenant
     */
    public function scopeTenantOwners($query)
    {
        return $query->where('is_tenant_owner', true);
    }

    /**
     * Scope pour les administrateurs
     */
    public function scopeAdmins($query)
    {
        return $query->whereIn('role_in_tenant', ['owner', 'admin']);
    }

    // ==================== METHODS ====================
    
    /**
     * Vérifier si l'utilisateur peut gérer le tenant
     */
    public function canManageTenant(): bool
    {
        return in_array($this->role_in_tenant, ['owner', 'admin']) || $this->is_tenant_owner === true;
    }

    /**
     * Vérifier si l'utilisateur est un simple utilisateur
     */
    public function isRegularUser(): bool
    {
        return $this->role_in_tenant === 'user' && !$this->is_tenant_owner;
    }

    /**
     * Changer le tenant de l'utilisateur
     */
    public function switchTenant($tenantId)
    {
        $this->tenant_id = $tenantId;
        $this->save();
        
        return $this;
    }

    /**
     * Mettre à jour le dernier accès
     */
    public function updateLastAccess($ip = null)
    {
        $this->last_login_at = now();
        $this->last_login_ip = $ip;
        $this->save();
        
        return $this;
    }

    // ==================== OVERRIDES ====================
    
    /**
     * Surcharge pour créer automatiquement le tenant_id
     */
    protected static function booted()
    {
        static::creating(function ($user) {
            if (session()->has('current_tenant_id') && !$user->tenant_id) {
                $user->tenant_id = session('current_tenant_id');
            }
        });
    }
}