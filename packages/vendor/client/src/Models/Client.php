<?php

namespace Vendor\Client\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use Vendor\CrmCore\Models\Tenant;
use Vendor\CrmCore\Traits\MultiTenantTrait;

class Client extends Model
{
    use SoftDeletes, MultiTenantTrait;

    protected $table = 'clients';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'assigned_to',
        'company_name',
        'contact_name',
        'email',
        'phone',
        'mobile',
        'fax',
        'website',
        'address',
        'address2',
        'city',
        'postal_code',
        'state',
        'country',
        'vat_number',
        'siret',
        'ape_code',
        'rcs',
        'type',
        'status',
        'source',
        'tags',
        'revenue',
        'potential_value',
        'payment_term',
        'industry',
        'employee_count',
        'notes',
        'custom_fields',
        'last_contact_at',
        'next_follow_up_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'custom_fields' => 'array',
        'revenue' => 'decimal:2',
        'potential_value' => 'decimal:2',
        'last_contact_at' => 'datetime',
        'next_follow_up_at' => 'datetime',
    ];

    // ==================== RELATIONS ====================
    
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function invoices()
    {
        return $this->hasMany('Vendor\Invoice\Models\Invoice');
    }

    public function contacts()
    {
        return $this->hasMany('Vendor\Contact\Models\Contact');
    }

    public function activities()
    {
        return $this->morphMany('Vendor\Activity\Models\Activity', 'activitable');
    }

    // ==================== SCOPES ====================
    
    public function scopeActive($query)
    {
        return $query->where('status', 'actif');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeBySource($query, $source)
    {
        return $query->where('source', $source);
    }

    public function scopeByIndustry($query, $industry)
    {
        return $query->where('industry', $industry);
    }

    public function scopeAssignedToUser($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeCreatedByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('company_name', 'like', "%{$search}%")
              ->orWhere('contact_name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%")
              ->orWhere('vat_number', 'like', "%{$search}%")
              ->orWhere('siret', 'like', "%{$search}%");
        });
    }

    public function scopeFilter($query, array $filters)
    {
        if (isset($filters['type']) && $filters['type']) {
            $query->byType($filters['type']);
        }

        if (isset($filters['status']) && $filters['status']) {
            $query->byStatus($filters['status']);
        }

        if (isset($filters['source']) && $filters['source']) {
            $query->bySource($filters['source']);
        }

        if (isset($filters['industry']) && $filters['industry']) {
            $query->byIndustry($filters['industry']);
        }

        if (isset($filters['assigned_to']) && $filters['assigned_to']) {
            $query->assignedToUser($filters['assigned_to']);
        }

        if (isset($filters['date_from']) && $filters['date_from']) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to']) && $filters['date_to']) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['revenue_min']) && $filters['revenue_min']) {
            $query->where('revenue', '>=', $filters['revenue_min']);
        }

        if (isset($filters['revenue_max']) && $filters['revenue_max']) {
            $query->where('revenue', '<=', $filters['revenue_max']);
        }

        if (isset($filters['search']) && $filters['search']) {
            $query->search($filters['search']);
        }

        return $query;
    }

    // ==================== ACCESSORS ====================
    
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->address2,
            $this->postal_code,
            $this->city,
            $this->state,
            $this->country
        ]);
        
        return implode(', ', $parts);
    }

    public function getTypeLabelAttribute(): string
    {
        $types = [
            'entreprise' => 'Entreprise',
            'particulier' => 'Particulier',
            'startup' => 'Startup',
            'association' => 'Association',
            'public' => 'Public',
        ];
        
        return $types[$this->type] ?? $this->type;
    }

    public function getStatusLabelAttribute(): string
    {
        $statuses = [
            'actif' => 'Actif',
            'inactif' => 'Inactif',
            'en_attente' => 'En attente',
            'suspendu' => 'Suspendu',
        ];
        
        return $statuses[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        $colors = [
            'actif' => 'success',
            'inactif' => 'danger',
            'en_attente' => 'warning',
            'suspendu' => 'secondary',
        ];
        
        return $colors[$this->status] ?? 'secondary';
    }

    public function getSourceLabelAttribute(): string
    {
        $sources = [
            'direct' => 'Direct',
            'site_web' => 'Site Web',
            'reference' => 'Recommandation',
            'reseau_social' => 'Réseau social',
            'autre' => 'Autre',
        ];
        
        return $sources[$this->source] ?? $this->source;
    }

    public function getInitialsAttribute(): string
    {
        return strtoupper(substr($this->company_name, 0, 2));
    }
}