<?php

namespace NexusExtensions\NotionWorkspace\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Vendor\CrmCore\Traits\MultiTenantTrait;

class NotionPage extends Model
{
    use SoftDeletes, MultiTenantTrait;

    protected $table = 'notion_pages';

    protected $fillable = [
        'tenant_id',
        'parent_id',
        'client_id',
        'owner_id',
        'title',
        'slug',
        'icon',
        'cover_color',
        'visibility',
        'content_json',
        'content_text',
        'is_favorite',
        'is_template',
        'is_archived',
        'sort_order',
        'last_edited_by',
        'last_edited_at',
    ];

    protected $casts = [
        'content_json' => 'array',
        'is_favorite' => 'boolean',
        'is_template' => 'boolean',
        'is_archived' => 'boolean',
        'last_edited_at' => 'datetime',
    ];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('title');
    }

    public function owner()
    {
        return $this->belongsTo(\App\Models\User::class, 'owner_id');
    }

    public function editor()
    {
        return $this->belongsTo(\App\Models\User::class, 'last_edited_by');
    }

    public function client()
    {
        return $this->belongsTo(\Vendor\Client\Models\Client::class, 'client_id');
    }

    public function shares()
    {
        return $this->hasMany(NotionPageShare::class, 'notion_page_id');
    }

    public function activities()
    {
        return $this->hasMany(NotionPageActivity::class, 'notion_page_id')->latest();
    }
}

