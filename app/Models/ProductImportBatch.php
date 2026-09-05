<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImportBatch extends Model
{
    public const TYPE_OCR_PDF = 'ocr_pdf';

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_IMPORTED = 'imported';

    protected $fillable = [
        'uuid',
        'store_id',
        'user_id',
        'type',
        'status',
        'source_name',
        'pdf_path',
        'zip_path',
        'zip_original_name',
        'preview',
        'error',
        'started_at',
        'completed_at',
        'imported_at',
    ];

    protected function casts(): array
    {
        return [
            'preview' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'imported_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
