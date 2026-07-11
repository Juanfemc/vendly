<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class StoreNotification extends Model
{
    public const TYPE_NEW_ORDER = 'new_order';
    public const TYPE_NEW_REVIEW = 'new_review';

    private static ?bool $supportsTable = null;

    protected $fillable = [
        'store_id',
        'user_id',
        'type',
        'title',
        'message',
        'action_url',
        'data',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    public static function supportsTable(): bool
    {
        return self::$supportsTable ??= Schema::hasTable('store_notifications');
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function markAsRead(): void
    {
        if ($this->read_at) {
            return;
        }

        $this->forceFill(['read_at' => now()])->save();
    }
}
