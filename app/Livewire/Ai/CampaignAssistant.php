<?php

namespace App\Livewire\Ai;

use App\Ai\Agents\CampaignKeywordAgent;
use App\Services\Ai\AiResponseSimplified;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Streaming\Events\StreamEnd;
use Laravel\Ai\Streaming\Events\TextDelta;
use Laravel\Ai\Streaming\Events\ToolCall;
use Livewire\Component;
use Livewire\Attributes\Url;

class CampaignAssistant extends Component
{
    private const MAX_MESSAGES = 100;

    public string $asin = '';

    #[Url(as: 'ai', except: 'minimized')]
    public string $viewMode = 'minimized'; // minimized, popup, fullscreen

    public string $prompt = '';
    public string $question = '';
    public string $answer = '';
    public ?string $error = null;
    public ?string $conversationId = null;
    public string $conversationTitle = 'Campaign AI';
    public bool $isStreaming = false;

    /**
     * @var array<int, array{role:string,content:string,is_html:bool}>
     */
    public array $messages = [];

    public string $selectedModelKey = 'gpt-oss:120b-cloud';

    // Filter options
    public string $campaignType = 'all';
    public string $country = 'all';

    /**
     * @var array<string, array{provider:string, model:string, timeout:int}>
     */
    public array $modelOptions = [
        'qwen3.5:cloud'     => ['provider' => 'ollama', 'model' => 'qwen3.5:cloud', 'timeout' => 500, 'accuracy' => 'low'],
        'gpt-oss:20b-cloud' => ['provider' => 'ollama', 'model' => 'gpt-oss:20b-cloud', 'timeout' => 500, 'accuracy' => 'high'],
        'gpt-oss:120b-cloud' => ['provider' => 'ollama', 'model' => 'gpt-oss:120b-cloud', 'timeout' => 500, 'accuracy' => 'high'],  
        'gpt-5'             => ['provider' => 'openai', 'model' => 'gpt-5', 'timeout' => 500, 'accuracy' => 'high'],
    ];

    /**
     * Campaign type options
     */
    public array $campaignTypes = [
        'all' => 'All Campaign Types',
        'SP'  => 'Sponsored Products (SP)',
        'SB'  => 'Sponsored Brands (SB)',
        'SD'  => 'Sponsored Display (SD)',
    ];

    /**
     * Country/Marketplace options
     */
    public array $countries = [
        'all' => 'All Countries',
        'US'  => 'United States',
        'CA'  => 'Canada',
        'MX'  => 'Mexico',
    ];

    public function mount(string $asin): void
    {
        $this->asin = $asin;

        // Check URL parameter for view mode
        if (request()->has('ai')) {
            $mode = request()->get('ai');
            if (in_array($mode, ['popup', 'fullscreen'], true)) {
                $this->viewMode = $mode;
            }
        }

        // Load existing conversation for this ASIN if exists
        $user = Auth::user();
        if ($user) {
            $cacheKey = "campaign_ai_conv:{$user->id}:{$asin}";
            
            $existing = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($user, $asin) {
                return DB::table('agent_conversations')
                    ->where('user_id', (int) $user->id)
                    ->where('metadata->asin', $asin)
                    ->where('metadata->type', 'campaign_assistant')
                    ->orderByDesc('updated_at')
                    ->first(['id', 'title']);
            });

            if ($existing) {
                $this->conversationId = (string) $existing->id;
                $this->conversationTitle = (string) ($existing->title ?: 'Campaign AI');
                $this->loadMessages();
            }
        }
    }

    public function toggleView(): void
    {
        if ($this->viewMode === 'minimized') {
            $this->viewMode = 'popup';
            $this->updateUrl();
            $this->dispatch('campaign-assistant-scroll-bottom');
        } else {
            $this->viewMode = 'minimized';
            $this->updateUrl();
        }
    }

    public function enterFullscreen(): void
    {
        $this->viewMode = 'fullscreen';
        $this->updateUrl();
        $this->dispatch('campaign-assistant-scroll-bottom');
    }

    public function exitFullscreen(): void
    {
        $this->viewMode = 'popup';
        $this->updateUrl();
        $this->dispatch('campaign-assistant-scroll-bottom');
    }

    public function closePopup(): void
    {
        $this->viewMode = 'minimized';
        $this->updateUrl();
    }

    public function clearError(): void
    {
        $this->error = null;
    }

    public function retryLastQuestion(): void
    {
        if ($this->question) {
            $this->clearError();
            $this->askStream();
        }
    }

    private function updateUrl(): void
    {
        // Livewire will automatically sync URL due to #[Url] attribute
        // This method is here for explicit clarity and future custom logic if needed
    }

    public function submitPrompt(): void
    {
        $this->reset('error');

        $data = $this->validate([
            'prompt' => ['required', 'string', 'max:4000'],
        ]);

        $this->question = trim($data['prompt']);
        $this->prompt = '';

        $this->js('$wire.askStream()');
    }

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

        // Retry loop for robustness
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                $this->streamProgress('phase', $attempt > 1 ? "Retrying analysis ($attempt/3)" : 'Analyzing your question');

                $agent = CampaignKeywordAgent::make()
                    ->setAsinContext($this->asin)
                    ->setFilters($this->campaignType, $this->country);

                $agent = $this->conversationId
                    ? $agent->continue($this->conversationId, as: $user)
                    : $agent->forUser($user);

                $stream = $agent->stream(
                    $this->question,
                    provider: $runtime['provider'],
                    model: $runtime['model'],
                    timeout: $runtime['timeout'],
                );

                $toolsToDetect = [
                    'UnifiedPerformanceQuery' => 'Querying Unified performance data',
                    'CampaignPerformanceLiteQuery' => 'Querying Campaign performance data',
                    'CampaignKeywordRecommendationsQuery' => 'Finding Recommended Keywords for products',
                    'WarehouseStockDetails' => 'Checking inventory',
                    'SpSearchTermSummaryTool' => 'Finding Search Term Summary',
                    'BrandAnalyticLiteQuery' => 'Querying Brand Analytics data',
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

                // If new chat, cache the ID and update metadata
                if (!$this->conversationId) {
                    $candidate = DB::table('agent_conversations')
                        ->where('user_id', (int) $user->id)
                        ->where('updated_at', '>=', $startedAt)
                        ->orderByDesc('updated_at')
                        ->first(['id', 'title']);

                    if ($candidate) {
                        DB::table('agent_conversations')->where('id', $candidate->id)->update([
                            'metadata' => json_encode([
                                'asin' => $this->asin,
                                'type' => 'campaign_assistant',
                            ]),
                        ]);
                        $this->conversationId = (string) $candidate->id;
                        $this->conversationTitle = (string) ($candidate->title ?: 'Campaign AI');
                        Cache::forget("campaign_ai_conv:{$user->id}:{$this->asin}");
                    }
                } else {
                    $title = DB::table('agent_conversations')->where('id', $this->conversationId)->value('title');
                    if ($title) $this->conversationTitle = (string) $title;
                }

                $this->loadMessages();
                $this->answer = '';
                $this->question = '';
                $this->isStreaming = false;
                $this->dispatch('campaign-assistant-scroll-bottom');

                return; // Success!
            } catch (\Throwable $e) {
                Log::error("Campaign AI attempt $attempt failed", ['msg' => $e->getMessage()]);
                
                if ($attempt === 3) {
                    $this->isStreaming = false;
                    $errorMessage = $e->getMessage();
                    
                    if (stripos($errorMessage, 'timeout') !== false || stripos($errorMessage, 'timed out') !== false) {
                        $this->error = '⏱️ Request timed out. The AI took too long to respond.';
                    } elseif (stripos($errorMessage, 'connection') !== false) {
                        $this->error = '🌐 Connection failed. Please check your internet.';
                    } elseif (stripos($errorMessage, 'rate') !== false) {
                        $this->error = '⚙️ Rate limit reached. Please wait a moment.';
                    } else {
                        $this->error = config('app.debug') ? $e::class . ': ' . $errorMessage : '❌ AI failed to respond.';
                    }
                    throw $e;
                }
                usleep(500000); // 0.5s pause before retry
            }
        }
    }

    /**
     * Send progress updates to the UI via wire:stream
     */
    private function streamProgress(string $type, string $label): void
    {
        $this->stream(to: 'progress', content: json_encode([
            'type'  => $type,
            'label' => $label,
        ], JSON_UNESCAPED_UNICODE), replace: true);
    }

    /**
     * Advanced delta extraction from streaming event
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
                    Log::warning("Failed to parse tool_results for campaign assistant: " . $e->getMessage());
                }
            }

            return [
                'role'    => $role,
                'content' => $formatted['content'],
                'is_html' => $formatted['is_html'],
                'trace_id' => $traceId,
            ];
        })->filter(function (array $message) {
            if (($message['role'] ?? '') === 'assistant' && trim((string) ($message['content'] ?? '')) === '') {
                return false;
            }

            return true;
        })->values()->all();
    }

    public function newChat(): void
    {
        // Clear conversation cache before resetting
        $user = Auth::user();
        if ($user) {
            Cache::forget("campaign_ai_conv:{$user->id}:{$this->asin}");
        }
        
        $this->reset('prompt', 'question', 'answer', 'error');
        $this->conversationId = null;
        $this->conversationTitle = 'Campaign AI';
        $this->messages = [];
        $this->isStreaming = false;

        // Scroll to bottom after clearing chat
        $this->dispatch('campaign-assistant-scroll-bottom');
    }

    public function render()
    {
        return view('livewire.ai.campaign-assistant');
    }
}
