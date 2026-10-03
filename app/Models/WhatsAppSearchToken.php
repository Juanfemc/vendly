<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppSearchToken extends Model
{
    public $timestamps = false;

    protected $table = 'whatsapp_search_tokens';

    protected $fillable = [
        'conversation_id',
        'message_id',
        'store_id',
        'token_hash',
        'source',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WhatsAppConversation::class, 'conversation_id');
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(WhatsAppChatMessage::class, 'message_id');
    }
}
