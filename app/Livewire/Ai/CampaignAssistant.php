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

    public string $selectedModelKey = 'gpt-5';

    // Filter options
    public string $campaignType = 'all';
    public string $country = 'all';

    /**
     * @var array<string, array{provider:string, model:string, timeout:int}>
     */
    public array $modelOptions = [
        'qwen3.5:cloud' => ['provider' => 'ollama', 'model' => 'qwen3.5:cloud', 'timeout' => 330],
        'gpt-5'         => ['provider' => 'openai', 'model' => 'gpt-5', 'timeout' => 330],
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

        try {
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

            $streamEndReason = null;

            foreach ($stream as $event) {
                if ($event instanceof StreamEnd) {
                    $streamEndReason = $event->reason;
                }

                $chunk = $this->extractDelta($event);
                if ($chunk !== '') {
                    $this->stream(to: 'answer', content: $chunk);
                    $this->answer .= $chunk;
                }
            }

            if ($streamEndReason === 'length') {
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

            // If new chat, find or create conversation with ASIN metadata
            if (!$this->conversationId) {
                $candidate = DB::table('agent_conversations')
                    ->where('user_id', (int) $user->id)
                    ->where('updated_at', '>=', $startedAt)
                    ->orderByDesc('updated_at')
                    ->first(['id', 'title']);

                if ($candidate) {
                    // Update metadata to mark this as campaign assistant for this ASIN
                    DB::table('agent_conversations')
                        ->where('id', $candidate->id)
                        ->update([
                            'metadata' => json_encode([
                                'asin' => $this->asin,
                                'type' => 'campaign_assistant',
                            ]),
                        ]);

                    $this->conversationId = (string) $candidate->id;
                    $this->conversationTitle = (string) ($candidate->title ?: 'Campaign AI');
                    
                    // Clear cache since we have a new conversation
                    Cache::forget("campaign_ai_conv:{$user->id}:{$this->asin}");
                }
            } else {
                // Refresh title in case it was updated
                $title = DB::table('agent_conversations')
                    ->where('id', $this->conversationId)
                    ->value('title');

                if ($title) {
                    $this->conversationTitle = (string) $title;
                }
            }

            $this->loadMessages();
            $this->answer = '';
            $this->isStreaming = false;
            $this->question = '';

            $this->dispatch('campaign-assistant-scroll-bottom');
        } catch (\Throwable $e) {
            $this->isStreaming = false;

            Log::error('Campaign AI streaming failed', [
                'conversation_id' => $this->conversationId,
                'user_id'         => Auth::id(),
                'asin'            => $this->asin,
                'provider'        => $runtime['provider'] ?? null,
                'model'           => $runtime['model'] ?? null,
                'message'         => $e->getMessage(),
            ]);

            // Provide user-friendly error messages
            $errorMessage = $e->getMessage();
            if (stripos($errorMessage, 'timeout') !== false || 
                stripos($errorMessage, 'timed out') !== false) {
                $this->error = '⏱️ Request timed out. The AI took too long to respond. Please try again or select a faster model.';
            } elseif (stripos($errorMessage, 'connection') !== false) {
                $this->error = '🌐 Connection failed. Please check your internet and try again.';
            } elseif (stripos($errorMessage, 'rate') !== false) {
                $this->error = '⚙️ Rate limit reached. Please wait a moment and try again.';
            } else {
                $this->error = config('app.debug')
                    ? $e::class . ': ' . $errorMessage
                    : '❌ AI failed to respond. Please try again.';
            }
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
