<?php

namespace Vendor\User\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use Vendor\CrmCore\Models\Tenant;

class UserInvitation extends Model
{
    protected $table = 'user_invitations';

    protected $fillable = [
        'tenant_id',
        'invited_by',
        'email',
        'role_in_tenant',
        'token',
        'expires_at',
        'accepted_at',
        'revoked_at',
        'revoked_reason',
        'resend_count',
        'last_resent_at',
    ];

    protected $casts = [
        'expires_at'    => 'datetime',
        'accepted_at'   => 'datetime',
        'revoked_at'    => 'datetime',
        'last_resent_at'=> 'datetime',
    ];

    // ── Relations ──────────────────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    // ── Accessors ──────────────────────────────────────────────────────────

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getIsAcceptedAttribute(): bool
    {
        return !is_null($this->accepted_at);
    }

    public function getIsRevokedAttribute(): bool
    {
        return !is_null($this->revoked_at);
    }

    public function getIsActiveAttribute(): bool
    {
        return !$this->is_expired && !$this->is_accepted && !$this->is_revoked;
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->is_accepted) return 'Acceptée';
        if ($this->is_revoked)  return 'Révoquée';
        if ($this->is_expired)  return 'Expirée';
        return 'En attente';
    }

    public function getStatusColorAttribute(): string
    {
        if ($this->is_accepted) return 'success';
        if ($this->is_revoked)  return 'danger';
        if ($this->is_expired)  return 'warning';
        return 'info';
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->whereNull('accepted_at')
                     ->whereNull('revoked_at')
                     ->where('expires_at', '>', now());
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopePending($query)
    {
        return $query->whereNull('accepted_at')->whereNull('revoked_at');
    }
}