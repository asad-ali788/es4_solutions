<?php

namespace App\Livewire\Ai;

use App\Ai\Agents\AiChatBot;
use App\Services\Ai\AiResponseSimplified;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Streaming\Events\StreamEnd;
use Laravel\Ai\Streaming\Events\TextDelta;
use Laravel\Ai\Streaming\Events\ToolCall;
use Livewire\Component;

class AiPlayground extends Component
{
    private const MAX_CONVERSATIONS = 30;
    private const MAX_MESSAGES = 120;
    private const MAX_CONTEXT_MESSAGES = 18;
    private const MAX_CONTEXT_TOTAL_CHARS = 120000;
    private const MAX_CONTEXT_SINGLE_MESSAGE_CHARS = 35000;

    public string $prompt = '';
    public string $question = '';
    public string $answer = '';
    public string $reasoning = '';
    public ?string $error = null;
    public ?string $conversationId = null;
    public bool $sidebarOpen = true;
    public bool $betaNoticeVisible = true;
    public bool $isStreaming = false;

    public array $conversations = [];
    public array $messages = [];

    public string $selectedModelKey = 'gpt-oss:120b-cloud';

    /**
     * Persist model selection in session
     */
    public function updatedSelectedModelKey($value): void
    {
        session(['ai_selected_model_key' => $value]);
    }

    public array $modelOptions = [
        // 'qwen3.5:cloud' => ['provider' => 'ollama', 'model' => 'qwen3.5:cloud', 'timeout' => 500, 'accuracy' => 'low', 'thinking' => false],
        'qwen3-coder-next:cloud' => ['provider' => 'ollama', 'model' => 'qwen3-coder-next:cloud', 'timeout' => 500, 'accuracy' => 'medium', 'thinking' => false],
        'gpt-oss:20b-cloud' => ['provider' => 'ollama', 'model' => 'gpt-oss:20b-cloud', 'timeout' => 500, 'accuracy' => 'medium', 'thinking' => 'low'],
        'gpt-oss:120b-cloud' => ['provider' => 'ollama', 'model' => 'gpt-oss:120b-cloud', 'timeout' => 500, 'accuracy' => 'high', 'thinking' => 'low'],
        'gpt-5' => ['provider' => 'openai', 'model' => 'gpt-5', 'timeout' => 500, 'accuracy' => 'high', 'thinking' => false],
    ];

    public function mount(): void
    {
        $this->loadConversations();

        // Load beta notice visibility from session (check if user closed it)
        $this->betaNoticeVisible = session('ai_beta_notice_visible', true);

        // Load model selection from session
        $sessionModel = session('ai_selected_model_key');
        if ($sessionModel && isset($this->modelOptions[$sessionModel])) {
            $this->selectedModelKey = $sessionModel;
        }

        if ($this->conversationId) {
            $this->loadMessages();
        }

        // \Log::info('TEST: AiPlayground mount called');
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
     * Includes automatic retry logic (up to 3 attempts) for increased reliability.
     */
    public function askStream(): void
    {
        $this->reset('error');
        $this->answer = '';
        $this->reasoning = '';
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

        $maxAttempts = 3;
        $startedAt = Carbon::now();

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                // Reset per-attempt state
                $this->answer = '';
                $this->reasoning = '';

                if ($this->shouldResetConversationForContextBudget()) {
                    $this->conversationId = null;
                    $this->messages = [];
                    $this->streamProgress('phase', 'Context too large, starting a fresh thread');
                }

                $agent = AiChatBot::make()->withOptions([
                    'thinking' => value($runtime['thinking'] ?? false)
                ]);
                $agent = $this->conversationId
                    ? $agent->continue($this->conversationId, as: $user)
                    : $agent->forUser($user);

                $this->streamProgress('phase', $attempt > 1 ? "Retrying analysis ($attempt/3)" : 'Analyzing your question');

                $stream = $agent->stream(
                    $this->question,
                    provider: $runtime['provider'],
                    model: $runtime['model'],
                    timeout: $runtime['timeout'],
                );

                $toolsToDetect = [
                    'UnifiedPerformanceQuery' => 'Querying Unified performance data',
                    'TopSellingProductsLiteQuery' => 'Finding top Selling products',
                    'CampaignPerformanceLiteQuery' => 'Querying Campaign performance data',
                    'CampaignKeywordRecommendationsQuery' => 'Finding Recommended Keywords for products',
                    'WarehouseStockDetails' => 'Checking inventory',
                    'SpSearchTermSummaryTool' => 'Finding Search Term Summary',
                    'BrandAnalyticLiteQuery' => 'Querying Brand Analytics data',
                    'InventoryLiteQuery' => 'Querying Inventory data',
                    'KeywordRankReportLiteQuery' => 'Tracking keyword ranks',
                ];
                $startedGenerating = false;
                $streamEndReason = null;

                foreach ($stream as $event) {
                    if ($event instanceof StreamEnd) {
                        $streamEndReason = $event->reason;
                    }

                    // Structured Tool Call Detection
                    if ($event instanceof ToolCall) {
                        $toolName = $event->toolCall->name;
                        $label = $toolsToDetect[$toolName] ?? "Executing $toolName";
                        $this->streamProgress('tool', $label);
                        continue; // Tool calls don't have text delta
                    }

                    // Extract text content
                    $chunk = $this->extractDelta($event);
                    if ($chunk === '') {
                        continue;
                    }

                    if (!$startedGenerating) {
                        $startedGenerating = true;
                        $this->streamProgress('phase', 'Generating response');
                    }

                    $this->stream(to: 'answer', content: $chunk);
                    $this->answer .= $chunk;
                }

                // Handle truncated responses
                if ($streamEndReason === 'length') {
                    $this->streamProgress('phase', 'Continuing response');
                    $continuation = $agent->stream(
                        'Continue exactly from where you stopped. Do not repeat previous content. Return only the remaining part.',
                        provider: $runtime['provider'],
                        model: $runtime['model'],
                        timeout: $runtime['timeout'],
                    );
                    foreach ($continuation as $event) {
                        $chunk = $this->extractDelta($event);
                        if ($chunk !== '') {
                            $this->stream(to: 'answer', content: $chunk);
                            $this->answer .= $chunk;
                        }
                    }
                }

                if (trim($this->answer) === '') {
                    throw new \Exception('AI returned an empty response.');
                }

                $this->streamProgress('done', 'Done');

                // Get conversation ID for new chats
                if (!$this->conversationId) {
                    $candidate = DB::table('agent_conversations')
                        ->where('user_id', (int) $user->id)
                        ->where('updated_at', '>=', $startedAt)
                        ->orderByDesc('updated_at')
                        ->value('id');

                    if ($candidate)
                        $this->conversationId = (string) $candidate;
                }

                $this->loadConversations();
                $this->loadMessages();
                $this->answer = '';
                $this->reasoning = '';
                $this->question = '';
                $this->isStreaming = false;
                $this->dispatch('ai-scroll-bottom');

                // Success! Exit the retry loop.
                return;
            } catch (\Throwable $e) {
                Log::error("AI streaming attempt $attempt failed", [
                    'conversation_id' => $this->conversationId,
                    'user_id' => Auth::id(),
                    'provider' => $runtime['provider'] ?? null,
                    'message' => $e->getMessage(),
                ]);

                if ($attempt < $maxAttempts) {
                    $this->streamProgress('phase', "AI connection interrupted, retrying ($attempt/3)...");
                    usleep(1000000); // Wait 1 second before retry
                    continue;
                }

                // Final failure
                $this->isStreaming = false;
                if ($this->isPromptTooLongError($e->getMessage())) {
                    $this->conversationId = null;
                    $this->messages = [];
                }
                $this->error = $e->getMessage();
            }
        }
    }



    /**
     * Simple delta extraction from streaming event
     */
    private function extractDelta(mixed $event): string
    {
        if ($event instanceof TextDelta) {
            return $event->delta;
        }

        if (is_array($event)) {
            $value = $event['text']
                ?? $event['delta']
                ?? $event['content']
                ?? null;

            return is_string($value) ? $value : '';
        }

        if (is_object($event)) {
            $value = $event->text
                ?? $event->delta
                ?? $event->content
                ?? null;

            if (is_string($value) && $value !== '') {
                return $value;
            }

            if (method_exists($event, 'toArray')) {
                $array = $event->toArray();
                if (is_array($array)) {
                    $arrayValue = $array['text']
                        ?? $array['delta']
                        ?? $array['content']
                        ?? null;

                    return is_string($arrayValue) ? $arrayValue : '';
                }
            }
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
        $this->reasoning = '';
        $this->loadMessages();
        $this->dispatch('ai-scroll-bottom');
    }

    public function newChat(): void
    {
        $this->reset('prompt', 'question', 'answer', 'reasoning', 'error');
        $this->conversationId = null;
        $this->messages = [];
        $this->isStreaming = false;
        $this->loadConversations();
        $this->dispatch('ai-scroll-bottom');
    }

    public function retryLastMessage(): void
    {
        $previousError = $this->error ?? '';
        $this->error = null;
        $this->answer = '';
        $this->reasoning = '';
        $this->isStreaming = false;

        if ($this->isPromptTooLongError($previousError)) {
            $this->conversationId = null;
            $this->messages = [];
        }

        // If question is still set from the failed attempt, re-stream it directly.
        if (trim($this->question) !== '') {
            $this->js('$wire.askStream()');
            return;
        }

        // Fallback: restore the last user message from history.
        $lastUser = collect($this->messages)
            ->last(fn($m) => ($m['role'] ?? '') === 'user');

        if ($lastUser) {
            $this->question = $lastUser['content'];
            $this->js('$wire.askStream()');
        }
    }

    private function isPromptTooLongError(string $message): bool
    {
        $normalized = strtolower($message);

        return str_contains($normalized, 'prompt too long')
            || str_contains($normalized, 'max context length')
            || str_contains($normalized, 'context length');
    }

    private function shouldResetConversationForContextBudget(): bool
    {
        if (!$this->conversationId) {
            return false;
        }

        $stats = DB::table('agent_conversation_messages')
            ->where('conversation_id', $this->conversationId)
            ->selectRaw('COUNT(*) as message_count')
            ->selectRaw('COALESCE(SUM(LENGTH(content)), 0) as total_chars')
            ->selectRaw('COALESCE(MAX(LENGTH(content)), 0) as max_chars')
            ->first();

        if (!$stats) {
            return false;
        }

        return (int) ($stats->message_count ?? 0) > self::MAX_CONTEXT_MESSAGES
            || (int) ($stats->total_chars ?? 0) > self::MAX_CONTEXT_TOTAL_CHARS
            || (int) ($stats->max_chars ?? 0) > self::MAX_CONTEXT_SINGLE_MESSAGE_CHARS;
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
            'id' => (string) $r->id,
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
            ->get(['role', 'content', 'tool_results'])
            ->reverse()
            ->values();

        $this->messages = $rows->map(function ($r) use ($formatter) {
            $role = (string) $r->role;
            $formatted = $formatter->formatMessage($role, (string) $r->content);

            // Extract trace_id from tool_results if present
            $traceId = null;
            if ($role === 'assistant' && !empty($r->tool_results)) {
                try {
                    $results = json_decode($r->tool_results, true);
                    if (is_array($results)) {
                        foreach ($results as $res) {
                            // The tool result is often a nested JSON string in 'result'
                            if (isset($res['result']) && is_string($res['result'])) {
                                $inner = json_decode($res['result'], true);
                                if (isset($inner['meta']['trace_id'])) {
                                    $traceId = $inner['meta']['trace_id'];
                                    break;
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    \Log::warning("Failed to parse tool_results for message: " . $e->getMessage());
                }
            }

            return [
                'role' => $role,
                'content' => $formatted['content'],
                'is_html' => $formatted['is_html'],
                'reasoning' => $formatted['reasoning'] ?? null,
                'trace_id' => $traceId,
            ];
        })->filter(function (array $message) {
            if (($message['role'] ?? '') === 'assistant' && trim((string) ($message['content'] ?? '')) === '') {
                return false;
            }

            return true;
        })->values()->all();
    }

    public function render()
    {
        // \Log::info('TEST: AiPlayground render called');
        return view('livewire.ai.ai-playground');
    }
}
