<?php

use App\Models\WhatsAppConversation;
use App\Services\WhatsAppSearchIndexer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('whatsapp_conversations') || Schema::hasTable('whatsapp_search_tokens')) {
            return;
        }

        Schema::create('whatsapp_search_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('whatsapp_conversations')->cascadeOnDelete();
            $table->foreignId('message_id')->nullable()->constrained('whatsapp_chat_messages')->cascadeOnDelete();
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
            $table->string('token_hash', 64);
            $table->string('source', 20);

            $table->index(['token_hash', 'conversation_id']);
            $table->index(['store_id', 'token_hash']);
            $table->index(['conversation_id', 'source']);
            $table->index('message_id');
        });

        $indexer = app(WhatsAppSearchIndexer::class);

        WhatsAppConversation::query()
            ->with('messages')
            ->orderBy('id')
            ->chunkById(50, function ($conversations) use ($indexer) {
                foreach ($conversations as $conversation) {
                    $indexer->indexConversation($conversation);

                    foreach ($conversation->messages as $message) {
                        $indexer->indexMessage($message);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_search_tokens');
    }
};
