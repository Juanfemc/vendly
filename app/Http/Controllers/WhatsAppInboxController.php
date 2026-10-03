<?php

namespace App\Http\Controllers;

use App\Models\WhatsAppConversation;
use App\Models\WhatsAppSearchToken;
use App\Services\WhatsAppInboxService;
use App\Services\WhatsAppSearchIndexer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class WhatsAppInboxController extends Controller
{
    public function __construct(
        private WhatsAppInboxService $inbox,
        private WhatsAppSearchIndexer $searchIndexer,
    )
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $whatsappSearch = trim((string) $request->query('q', ''));

        $this->inbox->syncRecentTemplateMessages(
            $user->isAdmin() ? null : $user->stores()->pluck('id')->all(),
        );

        $conversationsQuery = $this->visibleConversations($request)
            ->with('store')
            ->withMax('messages', 'created_at')
            ->orderByDesc('last_message_at')
            ->orderByDesc('id');

        $this->applyConversationSearch($request, $conversationsQuery, $whatsappSearch);

        $conversations = $conversationsQuery->paginate(15)->withQueryString();
        $selectedConversation = $this->selectedConversation($request);

        if ($selectedConversation) {
            $selectedConversation->load([
                'store',
                'messages' => fn ($query) => $query->with('sender')->oldest()->take(80),
            ]);

            if ($request->filled('conversation')) {
                $this->inbox->markConversationRead($selectedConversation);
            }
        }

        return view('admin.whatsapp.index', [
            'conversations' => $conversations,
            'selectedConversation' => $selectedConversation,
            'canManageAll' => $user->isAdmin(),
            'whatsappSearch' => $whatsappSearch,
        ]);
    }

    public function send(Request $request, WhatsAppConversation $conversation): RedirectResponse
    {
        $this->authorizeConversation($request, $conversation);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:4096'],
        ]);

        try {
            $this->inbox->sendReply($conversation, $request->user(), $validated['body']);
        } catch (Throwable $exception) {
            return redirect()
                ->route('admin.whatsapp.index', ['conversation' => $conversation->id])
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.whatsapp.index', ['conversation' => $conversation->id])
            ->with('success', 'Mensaje enviado por WhatsApp.');
    }

    private function selectedConversation(Request $request): ?WhatsAppConversation
    {
        $conversationId = $request->integer('conversation');

        if (! $conversationId) {
            return null;
        }

        $conversation = WhatsAppConversation::findOrFail($conversationId);
        $this->authorizeConversation($request, $conversation);

        return $conversation;
    }

    private function visibleConversations(Request $request)
    {
        $query = WhatsAppConversation::query();

        if (! $request->user()->isAdmin()) {
            $storeIds = $request->user()->stores()->pluck('id');
            $query->whereIn('store_id', $storeIds);
        }

        return $query;
    }

    private function applyConversationSearch(Request $request, $query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $digits = preg_replace('/\D+/', '', $search) ?: '';
        $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search) . '%';
        $phoneHashes = collect([$digits, strlen($digits) === 10 ? '57'.$digits : null])
            ->filter()
            ->unique()
            ->map(fn (string $phone) => hash('sha256', $phone))
            ->all();
        $searchTokenHashes = $this->searchIndexer->hashesForSearch($search);

        $databaseMatchIds = $this->visibleConversations($request)
            ->where(function ($databaseQuery) use ($like, $phoneHashes) {
                $databaseQuery->whereHas('store', function ($storeQuery) use ($like) {
                    $storeQuery
                        ->where('name', 'like', $like)
                        ->orWhere('slug', 'like', $like)
                        ->orWhere('whatsapp', 'like', $like);
                });

                if ($phoneHashes !== []) {
                    $databaseQuery->orWhereIn('contact_phone_hash', $phoneHashes);
                }
            })
            ->limit(1000)
            ->pluck('id');

        $indexedMatchIds = collect();

        if ($searchTokenHashes !== [] && $this->searchIndexer->canIndex()) {
            $indexedMatchIds = WhatsAppSearchToken::query()
                ->whereIn('token_hash', $searchTokenHashes)
                ->whereIn('conversation_id', $this->visibleConversations($request)->select('id'))
                ->select('conversation_id')
                ->groupBy('conversation_id')
                ->havingRaw('COUNT(DISTINCT token_hash) >= ?', [count($searchTokenHashes)])
                ->limit(1000)
                ->pluck('conversation_id');
        }

        $matchingIds = $databaseMatchIds
            ->merge($indexedMatchIds)
            ->unique()
            ->values()
            ->all();

        $matchingIds === []
            ? $query->whereRaw('1 = 0')
            : $query->whereKey($matchingIds);
    }

    private function authorizeConversation(Request $request, WhatsAppConversation $conversation): void
    {
        if ($request->user()->isAdmin()) {
            return;
        }

        $allowed = $request->user()
            ->stores()
            ->whereKey($conversation->store_id)
            ->exists();

        abort_unless($allowed, 403);
    }
}
