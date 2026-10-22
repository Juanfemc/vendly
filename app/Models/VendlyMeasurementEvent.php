<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendlyMeasurementEvent extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'custom_data' => 'array',
            'store_payload' => 'array',
            'user_data' => 'encrypted:array',
            'occurred_at' => 'immutable_datetime',
            'sent_at' => 'immutable_datetime',
            'discarded_at' => 'immutable_datetime',
            'next_attempt_at' => 'immutable_datetime',
        ];
    }
}
