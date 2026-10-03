<?php

namespace App\Services;

use App\Models\WhatsAppChatMessage;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppSearchToken;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class WhatsAppSearchIndexer
{
    private const MAX_TOKENS_PER_RECORD = 120;

    private static ?bool $canIndex = null;

    public function indexConversation(WhatsAppConversation $conversation): void
    {
        if (! $this->canIndex()) {
            return;
        }

        WhatsAppSearchToken::query()
            ->where('conversation_id', $conversation->id)
            ->where('source', 'conversation')
            ->delete();

        $this->insertTokens(
            $conversation->id,
            null,
            $conversation->store_id,
            'conversation',
            $this->tokensForIndex([
                $conversation->contact_name,
                $conversation->contact_phone,
            ]),
        );
    }

    public function indexMessage(WhatsAppChatMessage $message): void
    {
        if (! $this->canIndex()) {
            return;
        }

        WhatsAppSearchToken::query()
            ->where('message_id', $message->id)
            ->where('source', 'message')
            ->delete();

        $this->insertTokens(
            $message->conversation_id,
            $message->id,
            $message->store_id,
            'message',
            $this->tokensForIndex([$message->body]),
        );
    }

    public function hashesForSearch(string $search): array
    {
        return collect($this->tokensForQuery($search))
            ->map(fn (string $token) => $this->hashToken($token))
            ->unique()
            ->values()
            ->all();
    }

    public function canIndex(): bool
    {
        return self::$canIndex ??= Schema::hasTable('whatsapp_search_tokens');
    }

    private function insertTokens(int $conversationId, ?int $messageId, ?int $storeId, string $source, array $tokens): void
    {
        $rows = collect($tokens)
            ->unique()
            ->take(self::MAX_TOKENS_PER_RECORD)
            ->map(fn (string $token) => [
                'conversation_id' => $conversationId,
                'message_id' => $messageId,
                'store_id' => $storeId,
                'token_hash' => $this->hashToken($token),
                'source' => $source,
            ])
            ->values()
            ->all();

        if ($rows === []) {
            return;
        }

        WhatsAppSearchToken::query()->insertOrIgnore($rows);
    }

    private function tokensForIndex(array $values): array
    {
        return collect($values)
            ->flatMap(fn ($value) => $this->normalizeTokens((string) $value, true))
            ->unique()
            ->values()
            ->all();
    }

    private function tokensForQuery(string $value): array
    {
        return $this->normalizeTokens($value, false);
    }

    private function normalizeTokens(string $value, bool $includePrefixes): array
    {
        $normalized = Str::of($value)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->toString();

        if ($normalized === '') {
            return [];
        }

        return collect(explode(' ', $normalized))
            ->flatMap(fn (string $token) => $this->tokenVariants($token, $includePrefixes))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function tokenVariants(string $token, bool $includePrefixes): array
    {
        if (strlen($token) < 2) {
            return [];
        }

        if (ctype_digit($token)) {
            $variants = [$token];

            if (strlen($token) === 12 && str_starts_with($token, '57')) {
                $variants[] = substr($token, 2);
            }

            if (strlen($token) >= 10) {
                $variants[] = substr($token, -10);
            }

            return array_values(array_unique($variants));
        }

        $variants = [$token];

        if ($includePrefixes && strlen($token) > 3) {
            for ($length = 3; $length < strlen($token); $length++) {
                $variants[] = substr($token, 0, $length);
            }
        }

        return array_values(array_unique($variants));
    }

    private function hashToken(string $token): string
    {
        return hash_hmac('sha256', $token, (string) config('app.key'));
    }
}
