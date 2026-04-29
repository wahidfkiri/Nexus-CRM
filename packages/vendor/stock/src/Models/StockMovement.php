<?php

namespace Vendor\Stock\Models;

use Illuminate\Database\Eloquent\Model;
use Vendor\CrmCore\Traits\MultiTenantTrait;

class StockMovement extends Model
{
    use MultiTenantTrait;

    protected $table = 'stock_movements';

    protected $appends = [
        'direction_label',
        'movement_type_label',
        'display_reference',
        'display_reason',
        'happened_at_display',
    ];

    protected $fillable = [
        'tenant_id', 'user_id', 'article_id', 'delivery_note_id', 'delivery_note_item_id',
        'source_type', 'source_id', 'movement_type', 'direction', 'quantity', 'unit',
        'reference', 'reason', 'happened_at', 'notes', 'meta',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'happened_at' => 'datetime',
        'meta' => 'array',
    ];

    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    public function deliveryNote()
    {
        return $this->belongsTo(DeliveryNote::class, 'delivery_note_id');
    }

    public function deliveryNoteItem()
    {
        return $this->belongsTo(DeliveryNoteItem::class, 'delivery_note_item_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function getSignedQuantityAttribute(): float
    {
        $quantity = (float) $this->quantity;
        return $this->direction === 'out' ? -1 * $quantity : $quantity;
    }

    public function getDirectionLabelAttribute(): string
    {
        return $this->direction === 'out' ? 'Sortie' : 'Entrée';
    }

    public function getMovementTypeLabelAttribute(): string
    {
        $labels = (array) config('stock.movement_types', []);
        return (string) ($labels[$this->movement_type] ?? $this->movement_type);
    }

    public function getDisplayReferenceAttribute(): string
    {
        return match ((string) $this->reference) {
            'LEGACY-STOCK' => 'Reprise ancien stock',
            'OPENING-STOCK' => 'Stock initial',
            default => (string) ($this->reference ?: '—'),
        };
    }

    public function getDisplayReasonAttribute(): string
    {
        $reason = (string) ($this->reason ?? '');

        if ($this->movement_type === 'opening_balance' && $this->reference === 'LEGACY-STOCK') {
            return 'Stock initial repris depuis l’ancien champ de stock des articles.';
        }

        return match ($reason) {
            'Opening stock declared at article creation' => 'Stock initial déclaré à la création de l’article.',
            default => $reason !== '' ? $reason : '—',
        };
    }

    public function getHappenedAtDisplayAttribute(): string
    {
        return $this->happened_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '—';
    }
}
