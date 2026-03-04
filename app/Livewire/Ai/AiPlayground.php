<?php

namespace App\Livewire\Ai;

use App\Ai\Agents\AiChatBot;
use App\Services\Ai\AiResponseSimplified;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Streaming\Events\StreamEnd;
use Livewire\Component;

class AiPlayground extends Component
{
    private const MAX_CONVERSATIONS = 30;
    private const MAX_MESSAGES = 120;

    public string $prompt = '';
    public string $question = '';
    public string $answer = '';
    public ?string $error = null;
    public ?string $conversationId = null;
    public bool $sidebarOpen = true;
    public bool $betaNoticeVisible = true;
    public bool $isStreaming = false;

    public array $conversations = [];
    public array $messages = [];

    public string $selectedModelKey = 'qwen3.5:cloud';

    public array $modelOptions = [
        'qwen3.5:cloud' => ['provider' => 'ollama', 'model' => 'qwen3.5:cloud', 'timeout' => 330],
        'gpt-5'         => ['provider' => 'openai', 'model' => 'gpt-5', 'timeout' => 330],
    ];

    public function mount(): void
    {
        $this->loadConversations();
        
        // Load beta notice visibility from session (check if user closed it)
        $this->betaNoticeVisible = session('ai_beta_notice_visible', true);

        if ($this->conversationId) {
            $this->loadMessages();
        }
    }

    public function toggleSidebar(): void
    {
        $this->sidebarOpen = !$this->sidebarOpen;
    }

    public function closeSidebar(): void
    {
        $this->sidebarOpen = false;
    }

    public function closeBetaNotice(): void
    {
        $this->betaNoticeVisible = false;
        session(['ai_beta_notice_visible' => false]);
    }

    public function submitPrompt(): void
    {
        $this->reset('error');

        $data = $this->validate([
            'prompt' => ['required', 'string', 'max:4000'],
        ]);

        $this->question = trim($data['prompt']);
        $this->prompt = '';

        // Stream
        $this->js('$wire.askStream()');
    }

    /**
     * Simplified streaming - detect tools dynamically from response
     */
    public function askStream(): void
    {
        $this->reset('error');
        $this->answer = '';
        $this->isStreaming = true;

        $user = Auth::user();
        if (!$user) {
            $this->isStreaming = false;
            $this->error = 'Login required.';
            return;
        }

        $runtime = $this->modelOptions[$this->selectedModelKey] ?? null;
        if (!$runtime) {
            $this->isStreaming = false;
            $this->error = 'Invalid model selection.';
            return;
        }

        $startedAt = Carbon::now();

        try {
            $agent = AiChatBot::make();
            $agent = $this->conversationId
                ? $agent->continue($this->conversationId, as: $user)
                : $agent->forUser($user);

            $this->streamProgress('phase', 'Analyzing your question');

            $stream = $agent->stream(
                $this->question,
                provider: $runtime['provider'],
                model: $runtime['model'],
                timeout: $runtime['timeout'],
            );

            $startedGenerating = false;
            $toolsEmitted = [];
            $toolsToDetect = [
                'UnifiedPerformanceQuery' => 'Querying performance data',
                'TopSellingProducts'      => 'Finding top products',
                'WarehouseStockDetails'   => 'Checking inventory',
            ];

            $streamEndReason = null;

            foreach ($stream as $event) {
                if ($event instanceof StreamEnd) {
                    $streamEndReason = $event->reason;
                }

                $chunk = $this->extractDelta($event);
                if ($chunk !== '') {
                    // Detect tools in response
                    foreach ($toolsToDetect as $toolName => $shortLabel) {
                        if (!in_array($toolName, $toolsEmitted) && str_contains($chunk, $toolName)) {
                            $this->streamProgress('tool', $shortLabel);
                            $toolsEmitted[] = $toolName;
                        }
                    }

                    if (!$startedGenerating) {
                        $startedGenerating = true;
                        $this->streamProgress('phase', 'Generating response');
                    }

                    $this->stream(to: 'answer', content: $chunk);
                    $this->answer .= $chunk;
                }
            }

            if ($streamEndReason === 'length') {
                $this->streamProgress('phase', 'Continuing response');

                $continuationPrompt = 'Continue exactly from where you stopped. Do not repeat previous content. Return only the remaining part.';

                $continuation = $agent->stream(
                    $continuationPrompt,
                    provider: $runtime['provider'],
                    model: $runtime['model'],
                    timeout: $runtime['timeout'],
                );

                foreach ($continuation as $event) {
                    if ($event instanceof StreamEnd && $event->reason === 'length') {
                        $this->error = 'Response is very long and was truncated again. Please ask "continue" to fetch the next part.';
                    }

                    $chunk = $this->extractDelta($event);
                    if ($chunk !== '') {
                        $this->stream(to: 'answer', content: $chunk);
                        $this->answer .= $chunk;
                    }
                }
            }

            $this->streamProgress('done', 'Done');

            // Get conversation ID if new chat
            if (!$this->conversationId) {
                $candidate = DB::table('agent_conversations')
                    ->where('user_id', (int) $user->id)
                    ->where('updated_at', '>=', $startedAt)
                    ->orderByDesc('updated_at')
                    ->value('id');

                if ($candidate) {
                    $this->conversationId = (string) $candidate;
                }
            }

            // Reload messages from DB
            $this->loadConversations();
            $this->loadMessages();
            $this->answer = '';
            $this->question = '';
            $this->isStreaming = false;
            $this->dispatch('ai-scroll-bottom');

        } catch (\Throwable $e) {
            $this->isStreaming = false;
            Log::error('AI streaming failed', [
                'conversation_id' => $this->conversationId,
                'user_id'         => Auth::id(),
                'provider'        => $runtime['provider'] ?? null,
                'message'         => $e->getMessage(),
            ]);
            $this->error = config('app.debug') ? $e->getMessage() : 'Streaming failed.';
        }
    }

    /**
     * Simple delta extraction from streaming event
     */
    private function extractDelta(mixed $event): string
    {
        if (is_array($event)) {
            return (string) ($event['text'] ?? $event['delta'] ?? '');
        }
        if (is_object($event)) {
            return (string) ($event->text ?? $event->delta ?? '');
        }
        return '';
    }

    private function streamProgress(string $type, string $label): void
    {
        $this->stream(to: 'progress', content: $type . '|' . $label . PHP_EOL);
    }

    /**
     * Refresh conversation list + messages.
     */
    public function selectConversation(string $id): void
    {
        $this->conversationId = $id;
        $this->isStreaming = false;
        $this->answer = '';
        $this->loadMessages();
        $this->dispatch('ai-scroll-bottom');
    }

    public function newChat(): void
    {
        $this->reset('prompt', 'question', 'answer', 'error');
        $this->conversationId = null;
        $this->messages = [];
        $this->isStreaming = false;
        $this->loadConversations();
        $this->dispatch('ai-scroll-bottom');
    }

    public function deleteConversation(string $id): void
    {
        $user = Auth::user();
        if (!$user) {
            return;
        }

        DB::transaction(function () use ($id, $user) {
            DB::table('agent_conversation_messages')
                ->where('conversation_id', $id)
                ->delete();

            DB::table('agent_conversations')
                ->where('id', $id)
                ->where('user_id', (int) $user->id)
                ->delete();
        });

        if ($this->conversationId === $id) {
            $this->newChat();
        }

        $this->loadConversations();
    }

    private function loadConversations(): void
    {
        $user = Auth::user();
        if (!$user) {
            $this->conversations = [];
            return;
        }

        $rows = DB::table('agent_conversations')
            ->where('user_id', (int) $user->id)
            ->orderByDesc('updated_at')
            ->limit(self::MAX_CONVERSATIONS)
            ->get(['id', 'title']);

        $this->conversations = $rows->map(fn($r) => [
            'id'    => (string) $r->id,
            'title' => (string) ($r->title ?: 'New Chat'),
        ])->all();
    }

    private function loadMessages(): void
    {
        if (!$this->conversationId) {
            $this->messages = [];
            return;
        }

        $formatter = app(AiResponseSimplified::class);

        $rows = DB::table('agent_conversation_messages')
            ->where('conversation_id', $this->conversationId)
            ->orderByDesc('id')
            ->limit(self::MAX_MESSAGES)
            ->get(['role', 'content'])
            ->reverse()
            ->values();

        $this->messages = $rows->map(function ($r) use ($formatter) {
            $role = (string) $r->role;
            $formatted = $formatter->formatMessage($role, (string) $r->content);

            return [
                'role'    => $role,
                'content' => $formatted['content'],
                'is_html' => $formatted['is_html'],
            ];
        })->all();
    }

    public function render()
    {
        return view('livewire.ai.ai-playground');
    }
}
