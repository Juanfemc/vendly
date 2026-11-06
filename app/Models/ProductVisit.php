<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVisit extends Model
{
    protected $fillable = [
        'store_id',
        'product_id',
        'ip_hash',
        'user_agent_hash',
        'visited_on',
        'visited_at',
    ];

    protected $casts = [
        'visited_on' => 'date',
        'visited_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
