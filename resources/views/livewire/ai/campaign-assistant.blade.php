<div class="campaign-ai-assistant" wire:key="campaign-ai-{{ $asin }}">

    {{-- Minimized Orb --}}
    @if ($viewMode === 'minimized')
        <div class="ai-orb-container">
            <button type="button" class="ai-orb" wire:click="toggleView" title="Campaign AI - Click to chat">
                <span class="ai-orb-blob-bg" aria-hidden="true">
                    <svg viewBox="0 0 1200 1200" role="presentation" focusable="false">
                        <g class="ai-blob ai-blob-1">
                            <path d="M 100 600 q 0 -500, 500 -500 t 500 500 t -500 500 T 100 600 z" />
                        </g>
                        <g class="ai-blob ai-blob-2">
                            <path d="M 100 600 q -50 -400, 500 -500 t 450 550 t -500 500 T 100 600 z" />
                        </g>
                        <g class="ai-blob ai-blob-3">
                            <path d="M 100 600 q 0 -400, 500 -500 t 400 500 t -500 500 T 100 600 z" />
                        </g>
                        <g class="ai-blob ai-blob-4">
                            <path d="M 150 600 q 0 -600, 500 -500 t 500 550 t -500 500 T 150 600 z" />
                        </g>
                    </svg>
                </span>
                <img src="{{ asset('assets/images/ai.svg') }}" alt="Campaign AI" width="35" class="ai-orb-icon">
            </button>
        </div>
    @endif

    {{-- Popup Chat --}}
    @if ($viewMode === 'popup')
        <div class="ai-popup">
            <div class="ai-popup-header">
                <div class="ai-popup-title">
                <img src="{{ asset('assets/images/ai.svg') }}" alt="Campaign AI" width="25" class="ai-orb-icon">
                    {{-- <span>{{ $conversationTitle }}</span> --}}
                    <span class="ai-popup-asin">{{ $asin }}</span>
                    <div style="display: flex; gap: 6px; align-items: center; margin-left: 8px;">
                        @if ($campaignType !== 'all')
                            <span class="ai-filter-badge">{{ $campaignType }}</span>
                        @endif
                        @if ($country !== 'all')
                            <span class="ai-filter-badge">{{ $country }}</span>
                        @endif
                    </div>
                </div>
                <div class="ai-popup-actions">
                    {{-- Model Selector --}}
                    <div class="dropdown">
                        <button class="ai-header-btn dropdown-toggle" type="button" data-bs-toggle="dropdown"
                            title="Select Model">
                            <i class="bx bx-chip"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end ai-model-dropdown">
                            @foreach ($modelOptions as $label => $cfg)
                                <button class="dropdown-item {{ $selectedModelKey === $label ? 'active' : '' }}"
                                    type="button" wire:click="$set('selectedModelKey', '{{ $label }}')">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Campaign Type Filter --}}
                    <div class="dropdown">
                        <button class="ai-header-btn dropdown-toggle" type="button" data-bs-toggle="dropdown"
                            title="Filter by Campaign Type">
                            <i class="bx bx-filter-alt"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end ai-filter-dropdown">
                            @foreach ($campaignTypes as $value => $label)
                                <button class="dropdown-item {{ $campaignType === $value ? 'active' : '' }}"
                                    type="button" wire:click="$set('campaignType', '{{ $value }}')">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Country Filter --}}
                    <div class="dropdown">
                        <button class="ai-header-btn dropdown-toggle" type="button" data-bs-toggle="dropdown"
                            title="Filter by Country/Marketplace">
                            <i class="bx bx-world"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end ai-filter-dropdown">
                            @foreach ($countries as $value => $label)
                                <button class="dropdown-item {{ $country === $value ? 'active' : '' }}"
                                    type="button" wire:click="$set('country', '{{ $value }}')">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <button type="button" class="ai-header-btn" wire:click="newChat"
                        wire:confirm="Start a new conversation? Current chat will be saved." title="New Chat">
                        <i class="bx bx-plus"></i>
                    </button>
                    <button type="button" class="ai-header-btn" wire:click="enterFullscreen" title="Fullscreen">
                        <i class="bx bx-fullscreen"></i>
                    </button>
                    <button type="button" class="ai-header-btn" wire:click="closePopup" title="Minimize">
                        <i class="bx bx-minus"></i>
                    </button>
                </div>
            </div>

            <div class="ai-popup-body" id="campaignAiBody">
                @if ($error)
                    <div class="ai-popup-error">
                        <div class="alert alert-danger alert-sm mb-0">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div style="flex: 1;">
                                    <pre class="mb-2" style="white-space: pre-wrap; font-size: 13px;">{{ $error }}</pre>
                                </div>
                                <button type="button" onclick="$wire.clearError()" style="background: none; border: none; cursor: pointer; font-size: 18px; padding: 0 0 0 8px; color: #dc3545;">×</button>
                            </div>
                            <div style="display: flex; gap: 8px; margin-top: 8px;">
                                <button type="button" wire:click="retryLastQuestion" style="padding: 6px 12px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">
                                    Retry
                                </button>
                                <button type="button" onclick="$wire.clearError()" style="padding: 6px 12px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">
                                    Dismiss
                                </button>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Empty State --}}
                @if (count($messages) === 0 && !$question && !$answer)
                    <div class="ai-popup-empty">
                        <div class="ai-popup-empty-icon">
                            <svg width="34" height="34" viewBox="0 0 128 128" xmlns="http://www.w3.org/2000/svg">
                                <defs>
                                    <linearGradient id="empty-grad" x1="0" y1="0" x2="1"
                                        y2="1">
                                        <stop offset="0%" stop-color="#8A5BFF" />
                                        <stop offset="100%" stop-color="#FF3FA4" />
                                    </linearGradient>
                                </defs>
                                <path d="M64 28 L70 56 L98 62 L70 68 L64 96 L58 68 L30 62 L58 56 Z" fill="none"
                                    stroke="url(#empty-grad)" stroke-width="8" stroke-linejoin="round"
                                    stroke-linecap="round" />
                                <g stroke="url(#empty-grad)" stroke-width="8" stroke-linecap="round">
                                    <line x1="92" y1="36" x2="92" y2="48" />
                                    <line x1="86" y1="42" x2="98" y2="42" />
                                </g>
                                <circle cx="34" cy="86" r="6" fill="none" stroke="url(#empty-grad)"
                                    stroke-width="8" />
                            </svg>
                        </div>
                        <div class="ai-popup-empty-title">{{ $conversationTitle }}</div>
                        <div class="ai-popup-empty-text">
                            Ask me about campaigns, keywords, bids, ACOS or performance for
                            <strong>{{ $asin }}</strong>
                        </div>
                    </div>
                @endif

                {{-- Messages --}}
                <div class="ai-popup-thread">
                    @foreach ($messages as $m)
                        @if (($m['role'] ?? '') === 'user')
                            <div class="ai-popup-msg ai-popup-msg-user">
                                <div class="ai-popup-bubble">
                                    <div style="white-space: pre-wrap;">{{ $m['content'] }}</div>
                                </div>
                            </div>
                        @else
                            <div class="ai-popup-msg ai-popup-msg-bot">
                                <div class="ai-popup-avatar">
                                    <svg width="17" height="17" viewBox="0 0 128 128"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <defs>
                                            <linearGradient id="avatar-grad-{{ $loop->index }}" x1="0"
                                                y1="0" x2="1" y2="1">
                                                <stop offset="0%" stop-color="#8A5BFF" />
                                                <stop offset="100%" stop-color="#FF3FA4" />
                                            </linearGradient>
                                        </defs>
                                        <path d="M64 28 L70 56 L98 62 L70 68 L64 96 L58 68 L30 62 L58 56 Z"
                                            fill="none" stroke="url(#avatar-grad-{{ $loop->index }})"
                                            stroke-width="10" stroke-linejoin="round" stroke-linecap="round" />
                                        <g stroke="url(#avatar-grad-{{ $loop->index }})" stroke-width="10"
                                            stroke-linecap="round">
                                            <line x1="92" y1="36" x2="92" y2="48" />
                                            <line x1="86" y1="42" x2="98" y2="42" />
                                        </g>
                                        <circle cx="34" cy="86" r="6" fill="none"
                                            stroke="url(#avatar-grad-{{ $loop->index }})" stroke-width="10" />
                                    </svg>
                                </div>
                                <div
                                    class="ai-popup-bubble {{ ($m['is_html'] ?? false) === true ? 'ai-popup-bubble-rich' : '' }}">
                                    @if (($m['is_html'] ?? false) === true)
                                        {!! $m['content'] !!}
                                    @else
                                        <div style="white-space: pre-wrap;">{{ $m['content'] }}</div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endforeach

                    {{-- Streaming --}}
                    @if ($question)
                        <div class="ai-popup-msg ai-popup-msg-user">
                            <div class="ai-popup-bubble">
                                <div style="white-space: pre-wrap;">{{ $question }}</div>
                            </div>
                        </div>

                        <div class="ai-popup-msg ai-popup-msg-bot">
                            <div class="ai-popup-avatar">
                                <svg width="17" height="17" viewBox="0 0 128 128"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <defs>
                                        <linearGradient id="avatar-stream-grad" x1="0" y1="0"
                                            x2="1" y2="1">
                                            <stop offset="0%" stop-color="#8A5BFF" />
                                            <stop offset="100%" stop-color="#FF3FA4" />
                                        </linearGradient>
                                    </defs>
                                    <path d="M64 28 L70 56 L98 62 L70 68 L64 96 L58 68 L30 62 L58 56 Z" fill="none"
                                        stroke="url(#avatar-stream-grad)" stroke-width="10" stroke-linejoin="round"
                                        stroke-linecap="round" />
                                    <g stroke="url(#avatar-stream-grad)" stroke-width="10" stroke-linecap="round">
                                        <line x1="92" y1="36" x2="92" y2="48" />
                                        <line x1="86" y1="42" x2="98" y2="42" />
                                    </g>
                                    <circle cx="34" cy="86" r="6" fill="none"
                                        stroke="url(#avatar-stream-grad)" stroke-width="10" />
                                </svg>
                            </div>
                            <div class="ai-popup-bubble">
                                <div class="ai-typing" wire:loading wire:target="askStream">
                                    Typing <span class="ai-dots"><span></span><span></span><span></span></span>
                                </div>
                                <div wire:stream="answer" style="white-space: pre-wrap;">{{ $answer }}</div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="ai-popup-footer">
                <form wire:submit.prevent="submitPrompt" class="ai-popup-form">
                    <textarea class="ai-popup-input @error('prompt') is-invalid @enderror" wire:model.defer="prompt"
                        wire:keydown.ctrl.enter.prevent="submitPrompt" rows="1" placeholder="Ask about campaigns or keywords"></textarea>
                    <button class="ai-popup-send" type="submit" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="submitPrompt"><i class="bx bx-send"></i></span>
                        <span wire:loading wire:target="submitPrompt"><i class="bx bx-loader bx-spin"></i></span>
                    </button>
                </form>
                @error('prompt')
                    <div class="invalid-feedback d-block mt-1">{{ $message }}</div>
                @enderror
            </div>
        </div>
    @endif

    {{-- Fullscreen Mode --}}
    @if ($viewMode === 'fullscreen')
        <div class="ai-fullscreen">
            <div class="ai-fullscreen-header">
                <div class="ai-fullscreen-title">
                    <svg width="24" height="24" viewBox="0 0 128 128" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <linearGradient id="fs-title-grad" x1="0" y1="0" x2="1"
                                y2="1">
                                <stop offset="0%" stop-color="#8A5BFF" />
                                <stop offset="100%" stop-color="#FF3FA4" />
                            </linearGradient>
                        </defs>
                        <path d="M64 28 L70 56 L98 62 L70 68 L64 96 L58 68 L30 62 L58 56 Z" fill="none"
                            stroke="url(#fs-title-grad)" stroke-width="8" stroke-linejoin="round"
                            stroke-linecap="round" />
                        <g stroke="url(#fs-title-grad)" stroke-width="8" stroke-linecap="round">
                            <line x1="92" y1="36" x2="92" y2="48" />
                            <line x1="86" y1="42" x2="98" y2="42" />
                        </g>
                        <circle cx="34" cy="86" r="6" fill="none" stroke="url(#fs-title-grad)"
                            stroke-width="8" />
                    </svg>
                    <span>{{ $conversationTitle }}</span>
                    <span class="ai-fullscreen-asin">ASIN: {{ $asin }}</span>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        @if ($campaignType !== 'all')
                            <span class="ai-filter-badge">{{ $campaignType }}</span>
                        @endif
                        @if ($country !== 'all')
                            <span class="ai-filter-badge">{{ $country }}</span>
                        @endif
                    </div>
                </div>
                <div class="ai-fullscreen-actions">
                    {{-- Model Selector --}}
                    <div class="dropdown">
                        <button class="ai-header-btn dropdown-toggle" type="button" data-bs-toggle="dropdown"
                            title="Select Model">
                            <i class="bx bx-chip"></i>
                            <span class="ms-1">{{ $selectedModelKey }}</span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end ai-model-dropdown">
                            @foreach ($modelOptions as $label => $cfg)
                                <button class="dropdown-item {{ $selectedModelKey === $label ? 'active' : '' }}"
                                    type="button" wire:click="$set('selectedModelKey', '{{ $label }}')">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Campaign Type Filter --}}
                    <div class="dropdown">
                        <button class="ai-header-btn dropdown-toggle" type="button" data-bs-toggle="dropdown"
                            title="Filter by Campaign Type">
                            <i class="bx bx-filter-alt"></i>
                            <span class="ms-1">{{ $campaignTypes[$campaignType] ?? 'All' }}</span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end ai-filter-dropdown">
                            @foreach ($campaignTypes as $value => $label)
                                <button class="dropdown-item {{ $campaignType === $value ? 'active' : '' }}"
                                    type="button" wire:click="$set('campaignType', '{{ $value }}')">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Country Filter --}}
                    <div class="dropdown">
                        <button class="ai-header-btn dropdown-toggle" type="button" data-bs-toggle="dropdown"
                            title="Filter by Country/Marketplace">
                            <i class="bx bx-world"></i>
                            <span class="ms-1">{{ $countries[$country] ?? 'All' }}</span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end ai-filter-dropdown">
                            @foreach ($countries as $value => $label)
                                <button class="dropdown-item {{ $country === $value ? 'active' : '' }}"
                                    type="button" wire:click="$set('country', '{{ $value }}')">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <button type="button" class="ai-header-btn" wire:click="newChat"
                        wire:confirm="Start a new conversation? Current chat will be saved." title="New Chat">
                        <i class="bx bx-plus"></i>
                        <span class="ms-1">New Chat</span>
                    </button>
                    <button type="button" class="ai-header-btn" wire:click="exitFullscreen"
                        title="Exit Fullscreen">
                        <i class="bx bx-exit-fullscreen"></i>
                        <span class="ms-1">Exit</span>
                    </button>
                </div>
            </div>

            <div class="ai-fullscreen-body" id="campaignAiBodyFull">
                @if ($error)
                    <div class="ai-fullscreen-error">
                        <div class="alert alert-danger mb-0">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                                <div style="flex: 1;">
                                    <pre class="mb-0" style="white-space: pre-wrap; font-size: 14px;">{{ $error }}</pre>
                                </div>
                                <button type="button" onclick="$wire.clearError()" style="background: none; border: none; cursor: pointer; font-size: 20px; padding: 0 0 0 12px; color: #dc3545;">×</button>
                            </div>
                            <div style="display: flex; gap: 8px;">
                                <button type="button" wire:click="retryLastQuestion" style="padding: 8px 16px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 13px;">
                                    Retry
                                </button>
                                <button type="button" onclick="$wire.clearError()" style="padding: 8px 16px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 13px;">
                                    Dismiss
                                </button>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Empty State --}}
                @if (count($messages) === 0 && !$question && !$answer)
                    <div class="ai-fullscreen-empty">
                        <div class="ai-fullscreen-empty-icon">
                            <svg width="48" height="48" viewBox="0 0 128 128"
                                xmlns="http://www.w3.org/2000/svg">
                                <defs>
                                    <linearGradient id="fs-empty-grad" x1="0" y1="0" x2="1"
                                        y2="1">
                                        <stop offset="0%" stop-color="#8A5BFF" />
                                        <stop offset="100%" stop-color="#FF3FA4" />
                                    </linearGradient>
                                </defs>
                                <path d="M64 28 L70 56 L98 62 L70 68 L64 96 L58 68 L30 62 L58 56 Z" fill="none"
                                    stroke="url(#fs-empty-grad)" stroke-width="7" stroke-linejoin="round"
                                    stroke-linecap="round" />
                                <g stroke="url(#fs-empty-grad)" stroke-width="7" stroke-linecap="round">
                                    <line x1="92" y1="36" x2="92" y2="48" />
                                    <line x1="86" y1="42" x2="98" y2="42" />
                                </g>
                                <circle cx="34" cy="86" r="6" fill="none"
                                    stroke="url(#fs-empty-grad)" stroke-width="7" />
                            </svg>
                        </div>
                        <div class="ai-fullscreen-empty-title">{{ $conversationTitle }}</div>
                        <div class="ai-fullscreen-empty-text">
                            Get insights on campaigns, keywords, bids, ACOS, ROAS, and advertising strategies for
                            <strong>{{ $asin }}</strong>
                        </div>
                    </div>
                @endif

                {{-- Messages --}}
                <div class="ai-fullscreen-thread">
                    @foreach ($messages as $m)
                        @if (($m['role'] ?? '') === 'user')
                            <div class="ai-fullscreen-msg ai-fullscreen-msg-user">
                                <div class="ai-fullscreen-bubble">
                                    <div style="white-space: pre-wrap;">{{ $m['content'] }}</div>
                                </div>
                            </div>
                        @else
                            <div class="ai-fullscreen-msg ai-fullscreen-msg-bot">
                                <div class="ai-fullscreen-avatar">
                                    <svg width="20" height="20" viewBox="0 0 128 128"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <defs>
                                            <linearGradient id="fs-avatar-grad-{{ $loop->index }}" x1="0"
                                                y1="0" x2="1" y2="1">
                                                <stop offset="0%" stop-color="#8A5BFF" />
                                                <stop offset="100%" stop-color="#FF3FA4" />
                                            </linearGradient>
                                        </defs>
                                        <path d="M64 28 L70 56 L98 62 L70 68 L64 96 L58 68 L30 62 L58 56 Z"
                                            fill="none" stroke="url(#fs-avatar-grad-{{ $loop->index }})"
                                            stroke-width="10" stroke-linejoin="round" stroke-linecap="round" />
                                        <g stroke="url(#fs-avatar-grad-{{ $loop->index }})" stroke-width="10"
                                            stroke-linecap="round">
                                            <line x1="92" y1="36" x2="92" y2="48" />
                                            <line x1="86" y1="42" x2="98" y2="42" />
                                        </g>
                                        <circle cx="34" cy="86" r="6" fill="none"
                                            stroke="url(#fs-avatar-grad-{{ $loop->index }})" stroke-width="10" />
                                    </svg>
                                </div>
                                <div
                                    class="ai-fullscreen-bubble {{ ($m['is_html'] ?? false) === true ? 'ai-fullscreen-bubble-rich' : '' }}">
                                    @if (($m['is_html'] ?? false) === true)
                                        {!! $m['content'] !!}
                                    @else
                                        <div style="white-space: pre-wrap;">{{ $m['content'] }}</div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endforeach

                    {{-- Streaming --}}
                    @if ($question)
                        <div class="ai-fullscreen-msg ai-fullscreen-msg-user">
                            <div class="ai-fullscreen-bubble">
                                <div style="white-space: pre-wrap;">{{ $question }}</div>
                            </div>
                        </div>

                        <div class="ai-fullscreen-msg ai-fullscreen-msg-bot">
                            <div class="ai-fullscreen-avatar">
                                <svg width="20" height="20" viewBox="0 0 128 128"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <defs>
                                        <linearGradient id="fs-avatar-stream-grad" x1="0" y1="0"
                                            x2="1" y2="1">
                                            <stop offset="0%" stop-color="#8A5BFF" />
                                            <stop offset="100%" stop-color="#FF3FA4" />
                                        </linearGradient>
                                    </defs>
                                    <path d="M64 28 L70 56 L98 62 L70 68 L64 96 L58 68 L30 62 L58 56 Z" fill="none"
                                        stroke="url(#fs-avatar-stream-grad)" stroke-width="10"
                                        stroke-linejoin="round" stroke-linecap="round" />
                                    <g stroke="url(#fs-avatar-stream-grad)" stroke-width="10" stroke-linecap="round">
                                        <line x1="92" y1="36" x2="92" y2="48" />
                                        <line x1="86" y1="42" x2="98" y2="42" />
                                    </g>
                                    <circle cx="34" cy="86" r="6" fill="none"
                                        stroke="url(#fs-avatar-stream-grad)" stroke-width="10" />
                                </svg>
                            </div>
                            <div class="ai-fullscreen-bubble">
                                <div class="ai-typing" wire:loading wire:target="askStream">
                                    Typing <span class="ai-dots"><span></span><span></span><span></span></span>
                                </div>
                                <div wire:stream="answer" style="white-space: pre-wrap;">{{ $answer }}</div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="ai-fullscreen-footer">
                <form wire:submit.prevent="submitPrompt" class="ai-fullscreen-form">
                    <textarea class="ai-fullscreen-input @error('prompt') is-invalid @enderror" wire:model.defer="prompt"
                        wire:keydown.ctrl.enter.prevent="submitPrompt" rows="1"
                        placeholder="Ask about campaigns, keywords, bids, ACOS, targeting strategies..."></textarea>
                    <button class="ai-fullscreen-send" type="submit" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="submitPrompt"><i class="bx bx-send"></i></span>
                        <span wire:loading wire:target="submitPrompt"><i class="bx bx-loader bx-spin"></i></span>
                    </button>
                </form>
                @error('prompt')
                    <div class="invalid-feedback d-block mt-1">{{ $message }}</div>
                @enderror
            </div>
        </div>
    @endif

    <style>
        /* ============================================
           ORB BUTTON (Minimized State)
           ============================================ */
        .ai-orb-container {
            position: fixed;
            bottom: 28px;
            right: 28px;
            z-index: 9999;
        }

        .ai-orb {
            position: relative;
            width: 50px;
            height: 60px;
            border-radius: 0;
            border: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 3;
            transition: transform 0.25s ease;
            overflow: visible;
            padding: 0;
            background: transparent;
        }

        .ai-orb:hover {
            transform: scale(1.05);
        }

        .ai-orb:active {
            transform: scale(0.96);
        }

        .ai-orb-icon {
            position: relative;
            z-index: 3;
        }

        .ai-orb-blob-bg {
            position: absolute;
            inset: -120%;
            z-index: 1;
        }

        .ai-orb-blob-bg svg {
            width: 60%;
            height: 100%;
        }

        .ai-blob {
            animation: aiBlobRotate 25s infinite alternate ease-in-out;
            transform-origin: 50% 50%;
            opacity: 0.7;
        }

        .ai-blob path {
            transform-origin: 50% 50%;
            transform: scale(0.8);
        }

        .ai-blob-1 path {
            fill: #bb74ff;
            filter: blur(1rem);
        }

        .ai-blob-2 {
            animation-duration: 18s;
            animation-direction: alternate-reverse;
        }

        .ai-blob-2 path {
            fill: #7c7dff;
            filter: blur(0.75rem);
            transform: scale(0.78);
        }

        .ai-blob-3 {
            animation-duration: 23s;
        }

        .ai-blob-3 path {
            fill: #a0f8ff;
            filter: blur(0.5rem);
            transform: scale(0.76);
        }

        .ai-blob-4 {
            animation-duration: 31s;
            animation-direction: alternate-reverse;
            opacity: 0.9;
        }

        .ai-blob-4 path {
            fill: #ffffff;
            filter: blur(10rem);
            transform: scale(0.5);
        }

        @keyframes aiBlobRotate {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        @keyframes pulse {
            0% {
                transform: scale(0.92);
                opacity: 0.9;
            }

            50% {
                transform: scale(1.18);
                opacity: 0.4;
            }

            100% {
                transform: scale(1.45);
                opacity: 0;
            }
        }

        /* ============================================
           POPUP MODE
           ============================================ */
        .ai-popup {
            position: fixed;
            bottom: 24px;
            right: 24px;
            width: 620px;
            height: 600px;
            max-height: calc(100vh - 80px);
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(15, 23, 42, .25);
            z-index: 9999;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid rgba(148, 163, 184, .2);
            animation: popupSlideIn 0.3s ease-out;
        }

        html[data-bs-theme="dark"] .ai-popup {
            background: #0f172a;
            border-color: rgba(255, 255, 255, .1);
        }

        @keyframes popupSlideIn {
            from {
                transform: translateY(20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .ai-popup-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 18px;
            background: linear-gradient(135deg, rgba(138, 91, 255, .12), rgba(255, 63, 164, .08));
            border-bottom: 1px solid rgba(198, 94, 255, .2);
            flex-shrink: 0;
        }

        html[data-bs-theme="dark"] .ai-popup-header {
            background: linear-gradient(135deg, rgba(124, 58, 237, .18), rgba(255, 45, 148, .12));
            border-bottom-color: rgba(198, 94, 255, .25);
        }

        .ai-popup-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            font-size: 15px;
            color: #0f172a;
        }

        html[data-bs-theme="dark"] .ai-popup-title {
            color: #e5e7eb;
        }

        .ai-popup-title i {
            font-size: 22px;
            color: #8A5BFF;
            filter: drop-shadow(0 1px 2px rgba(138, 91, 255, .3));
        }

        .ai-popup-asin {
            font-size: 11px;
            padding: 3px 10px;
            border-radius: 6px;
            background: rgba(138, 91, 255, .12);
            color: #7C3AED;
            font-weight: 600;
            border: 1px solid rgba(138, 91, 255, .2);
        }

        html[data-bs-theme="dark"] .ai-popup-asin {
            background: rgba(198, 94, 255, .2);
            color: #E9D5FF;
            border-color: rgba(198, 94, 255, .3);
        }

        .ai-filter-badge {
            font-size: 10px;
            padding: 3px 8px;
            border-radius: 5px;
            background: rgba(59, 130, 246, .15);
            color: #1d4ed8;
            font-weight: 600;
            border: 1px solid rgba(59, 130, 246, .25);
            white-space: nowrap;
        }

        html[data-bs-theme="dark"] .ai-filter-badge {
            background: rgba(59, 130, 246, .2);
            color: #93c5fd;
            border-color: rgba(59, 130, 246, .3);
        }

        .ai-popup-actions {
            display: flex;
            gap: 4px;
        }

        .ai-header-btn {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            border: 1px solid rgba(148, 163, 184, .2);
            background: white;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.15s ease;
            font-size: 16px;
        }

        html[data-bs-theme="dark"] .ai-header-btn {
            background: rgba(255, 255, 255, .04);
            border-color: rgba(255, 255, 255, .1);
            color: #94a3b8;
        }

        .ai-header-btn:hover {
            background: #f1f5f9;
            color: #2563eb;
        }

        html[data-bs-theme="dark"] .ai-header-btn:hover {
            background: rgba(255, 255, 255, .08);
            color: #60a5fa;
        }

        .ai-popup-body {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            padding: 16px;
            background: #fafbfc;
        }

        html[data-bs-theme="dark"] .ai-popup-body {
            background: #0b1220;
        }

        .ai-popup-empty {
            text-align: center;
            padding: 40px 20px;
        }

        .ai-popup-empty-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 18px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(138, 91, 255, .15), rgba(255, 63, 164, .1));
            border: 1px solid rgba(198, 94, 255, .2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 34px;
            color: #8A5BFF;
            box-shadow: 0 4px 12px rgba(138, 91, 255, .15);
        }

        .ai-popup-empty-title {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 8px;
        }

        html[data-bs-theme="dark"] .ai-popup-empty-title {
            color: #e5e7eb;
        }

        .ai-popup-empty-text {
            font-size: 13px;
            color: #64748b;
            line-height: 1.5;
        }

        html[data-bs-theme="dark"] .ai-popup-empty-text {
            color: #94a3b8;
        }

        .ai-popup-thread {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .ai-popup-msg {
            display: flex;
            gap: 8px;
            align-items: flex-end;
        }

        .ai-popup-msg-user {
            justify-content: flex-end;
        }

        .ai-popup-msg-bot {
            justify-content: flex-start;
        }

        .ai-popup-avatar {
            width: 30px;
            height: 30px;
            border-radius: 9px;
            background: linear-gradient(135deg, rgba(138, 91, 255, .15), rgba(255, 63, 164, .1));
            border: 1px solid rgba(198, 94, 255, .2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #8A5BFF;
            font-size: 17px;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(138, 91, 255, .12);
        }

        .ai-popup-bubble {
            max-width: 75%;
            padding: 10px 14px;
            border-radius: 14px;
            font-size: 13px;
            line-height: 1.5;
        }

        .ai-popup-msg-user .ai-popup-bubble {
            background: linear-gradient(135deg, rgba(138, 91, 255, .18), rgba(255, 63, 164, .14));
            color: #0f172a;
            border: 1px solid rgba(138, 91, 255, .25);
            box-shadow: 0 2px 8px rgba(138, 91, 255, .08);
        }

        html[data-bs-theme="dark"] .ai-popup-msg-user .ai-popup-bubble {
            background: linear-gradient(135deg, rgba(124, 58, 237, .25), rgba(255, 45, 148, .18));
            color: #e5e7eb;
            border-color: rgba(198, 94, 255, .3);
        }

        .ai-popup-msg-bot .ai-popup-bubble {
            background: white;
            color: #0f172a;
            border: 1px solid rgba(148, 163, 184, .2);
            box-shadow: 0 2px 8px rgba(15, 23, 42, .04);
            max-width: 85%;
        }

        html[data-bs-theme="dark"] .ai-popup-msg-bot .ai-popup-bubble {
            background: #1e293b;
            color: #e5e7eb;
            border-color: rgba(255, 255, 255, .1);
        }

        .ai-popup-msg-bot .ai-popup-bubble.ai-popup-bubble-rich {
            width: 100%;
            max-width: 95%;
            overflow-x: auto;
            overflow-y: hidden;
        }

        .ai-popup-footer {
            padding: 12px 16px;
            background: white;
            border-top: 1px solid rgba(148, 163, 184, .2);
            flex-shrink: 0;
        }

        html[data-bs-theme="dark"] .ai-popup-footer {
            background: #0f172a;
            border-top-color: rgba(255, 255, 255, .1);
        }

        .ai-popup-form {
            display: flex;
            gap: 8px;
            align-items: flex-end;
        }

        .ai-popup-input {
            flex: 1;
            padding: 10px 12px;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, .25);
            background: #f8f9fa;
            font-size: 13px;
            resize: none;
            min-height: 40px;
            max-height: 120px;
            font-family: inherit;
        }

        html[data-bs-theme="dark"] .ai-popup-input {
            background: rgba(255, 255, 255, .04);
            border-color: rgba(255, 255, 255, .1);
            color: #e5e7eb;
        }

        .ai-popup-input:focus {
            outline: none;
            border-color: #8A5BFF;
            box-shadow: 0 0 0 3px rgba(138, 91, 255, .1);
        }

        .ai-popup-send {
            width: 42px;
            height: 42px;
            border-radius: 11px;
            background: linear-gradient(135deg, #8A5BFF, #C75EFF);
            border: 0;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(138, 91, 255, .3);
        }

        .ai-popup-send:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(138, 91, 255, .4);
            background: linear-gradient(135deg, #7C3AED, #B84EFF);
        }

        .ai-popup-send:active {
            transform: translateY(0);
        }

        /* ============================================
           FULLSCREEN MODE
           ============================================ */
        .ai-fullscreen {
            position: fixed;
            inset: 0;
            background: white;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            animation: fullscreenFadeIn 0.2s ease-out;
        }

        html[data-bs-theme="dark"] .ai-fullscreen {
            background: #0b1220;
        }

        @keyframes fullscreenFadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .ai-fullscreen-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 24px;
            background: white;
            border-bottom: 1px solid rgba(148, 163, 184, .2);
            flex-shrink: 0;
        }

        html[data-bs-theme="dark"] .ai-fullscreen-header {
            background: #0f172a;
            border-bottom-color: rgba(255, 255, 255, .1);
        }

        .ai-fullscreen-title {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
            font-size: 18px;
            color: #0f172a;
        }

        html[data-bs-theme="dark"] .ai-fullscreen-title {
            color: #e5e7eb;
        }

        .ai-fullscreen-title i {
            font-size: 24px;
            color: #2563eb;
        }

        .ai-fullscreen-asin {
            font-size: 13px;
            padding: 4px 12px;
            border-radius: 8px;
            background: rgba(37, 99, 235, .12);
            color: #1d4ed8;
            font-weight: 600;
        }

        html[data-bs-theme="dark"] .ai-fullscreen-asin {
            background: rgba(59, 130, 246, .2);
            color: #93c5fd;
        }

        .ai-fullscreen-actions {
            display: flex;
            gap: 8px;
        }

        .ai-fullscreen-actions .ai-header-btn {
            width: auto;
            padding: 0 16px;
            height: 36px;
            font-size: 14px;
        }

        .ai-fullscreen-body {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            padding: 24px;
            background: #fafbfc;
        }

        html[data-bs-theme="dark"] .ai-fullscreen-body {
            background: #0b1220;
        }

        .ai-fullscreen-empty {
            max-width: 600px;
            margin: 100px auto 0;
            text-align: center;
        }

        .ai-fullscreen-empty-icon {
            width: 100px;
            height: 100px;
            margin: 0 auto 24px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(138, 91, 255, .12), rgba(255, 63, 164, .08));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: #8A5BFF;
        }

        .ai-fullscreen-empty-title {
            font-size: 32px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 12px;
        }

        html[data-bs-theme="dark"] .ai-fullscreen-empty-title {
            color: #e5e7eb;
        }

        .ai-fullscreen-empty-text {
            font-size: 16px;
            color: #64748b;
            line-height: 1.6;
        }

        html[data-bs-theme="dark"] .ai-fullscreen-empty-text {
            color: #94a3b8;
        }

        .ai-fullscreen-thread {
            max-width: 1600px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .ai-fullscreen-msg {
            display: flex;
            gap: 12px;
            align-items: flex-end;
        }

        .ai-fullscreen-msg-user {
            justify-content: flex-end;
        }

        .ai-fullscreen-msg-bot {
            justify-content: flex-start;
        }

        .ai-fullscreen-avatar {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: linear-gradient(135deg, rgba(138, 91, 255, .12), rgba(255, 63, 164, .08));
            display: flex;
            align-items: center;
            justify-content: center;
            color: #8A5BFF;
            font-size: 20px;
            flex-shrink: 0;
        }

        .ai-fullscreen-bubble {
            max-width: 80%;
            padding: 14px 18px;
            border-radius: 16px;
            font-size: 15px;
            line-height: 1.6;
        }

        .ai-fullscreen-msg-user .ai-fullscreen-bubble {
            background: linear-gradient(135deg, rgba(138, 91, 255, .16), rgba(255, 63, 164, .12));
            color: #0f172a;
            border: 1px solid rgba(138, 91, 255, .25);
        }

        html[data-bs-theme="dark"] .ai-fullscreen-msg-user .ai-fullscreen-bubble {
            background: linear-gradient(135deg, rgba(124, 58, 237, .25), rgba(255, 45, 148, .18));
            color: #e5e7eb;
            border-color: rgba(198, 94, 255, .3);
        }

        .ai-fullscreen-msg-bot .ai-fullscreen-bubble {
            background: white;
            color: #0f172a;
            border: 1px solid rgba(148, 163, 184, .2);
            box-shadow: 0 4px 12px rgba(15, 23, 42, .06);
            max-width: 90%;
        }

        html[data-bs-theme="dark"] .ai-fullscreen-msg-bot .ai-fullscreen-bubble {
            background: #1e293b;
            color: #e5e7eb;
            border-color: rgba(255, 255, 255, .1);
        }

        .ai-fullscreen-msg-bot .ai-fullscreen-bubble.ai-fullscreen-bubble-rich {
            width: 100%;
            max-width: 98%;
            overflow-x: auto;
            overflow-y: hidden;
        }

        /* ============================================
           TABLE STYLES
           ============================================ */
        .ai-popup-bubble table,
        .ai-fullscreen-bubble table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            border: 1px solid rgba(148, 163, 184, .35);
            border-radius: 8px;
            overflow: hidden;
            margin: 8px 0;
        }

        .ai-popup-bubble table th,
        .ai-popup-bubble table td,
        .ai-fullscreen-bubble table th,
        .ai-fullscreen-bubble table td {
            border: 1px solid rgba(148, 163, 184, .28);
            padding: 8px 12px;
            text-align: left;
            vertical-align: top;
        }

        .ai-popup-bubble table th,
        .ai-fullscreen-bubble table th {
            background: rgba(148, 163, 184, .08);
            font-weight: 700;
            font-size: 12px;
            color: #334155;
            white-space: nowrap;
        }

        .ai-popup-bubble table td,
        .ai-fullscreen-bubble table td {
            font-size: 12px;
            color: #475569;
        }

        html[data-bs-theme="dark"] .ai-popup-bubble table,
        html[data-bs-theme="dark"] .ai-fullscreen-bubble table {
            border-color: rgba(148, 163, 184, .25);
        }

        html[data-bs-theme="dark"] .ai-popup-bubble table th,
        html[data-bs-theme="dark"] .ai-popup-bubble table td,
        html[data-bs-theme="dark"] .ai-fullscreen-bubble table th,
        html[data-bs-theme="dark"] .ai-fullscreen-bubble table td {
            border-color: rgba(148, 163, 184, .2);
        }

        html[data-bs-theme="dark"] .ai-popup-bubble table th,
        html[data-bs-theme="dark"] .ai-fullscreen-bubble table th {
            background: rgba(148, 163, 184, .12);
            color: #cbd5e1;
        }

        html[data-bs-theme="dark"] .ai-popup-bubble table td,
        html[data-bs-theme="dark"] .ai-fullscreen-bubble table td {
            color: #cbd5e1;
        }

        /* HTML Elements Support */
        .ai-popup-bubble strong,
        .ai-fullscreen-bubble strong {
            font-weight: 700;
            color: #1e293b;
        }

        html[data-bs-theme="dark"] .ai-popup-bubble strong,
        html[data-bs-theme="dark"] .ai-fullscreen-bubble strong {
            color: #f1f5f9;
        }

        .ai-popup-bubble code,
        .ai-fullscreen-bubble code {
            background: rgba(148, 163, 184, .12);
            padding: 2px 6px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 11px;
        }

        .ai-popup-bubble pre,
        .ai-fullscreen-bubble pre {
            background: rgba(148, 163, 184, .08);
            padding: 12px;
            border-radius: 8px;
            overflow-x: auto;
            margin: 8px 0;
        }

        .ai-popup-bubble ul,
        .ai-popup-bubble ol,
        .ai-fullscreen-bubble ul,
        .ai-fullscreen-bubble ol {
            margin: 8px 0;
            padding-left: 24px;
        }

        .ai-popup-bubble li,
        .ai-fullscreen-bubble li {
            margin: 4px 0;
        }

        .ai-fullscreen-footer {
            padding: 16px 24px;
            background: white;
            border-top: 1px solid rgba(148, 163, 184, .2);
            flex-shrink: 0;
        }

        html[data-bs-theme="dark"] .ai-fullscreen-footer {
            background: #0f172a;
            border-top-color: rgba(255, 255, 255, .1);
        }

        .ai-fullscreen-form {
            max-width: 1600px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            gap: 12px;
            align-items: flex-end;
        }

        .ai-fullscreen-input {
            flex: 1;
            padding: 12px 16px;
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, .25);
            background: #f8f9fa;
            font-size: 15px;
            resize: none;
            min-height: 50px;
            max-height: 150px;
            font-family: inherit;
        }

        html[data-bs-theme="dark"] .ai-fullscreen-input {
            background: rgba(255, 255, 255, .04);
            border-color: rgba(255, 255, 255, .1);
            color: #e5e7eb;
        }

        .ai-fullscreen-input:focus {
            outline: none;
            border-color: #8A5BFF;
        }

        .ai-fullscreen-send {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: linear-gradient(135deg, #8A5BFF, #C75EFF);
            border: 0;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.15s ease;
            flex-shrink: 0;
            font-size: 20px;
        }

        .ai-fullscreen-send:hover {
            transform: scale(1.05);
            background: linear-gradient(135deg, #7C3AED, #B84EFF);
        }

        /* ============================================
           SHARED UTILITIES
           ============================================ */
        .ai-typing {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 8px;
        }

        html[data-bs-theme="dark"] .ai-typing {
            color: #94a3b8;
        }

        .ai-dots span {
            display: inline-block;
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background: #64748b;
            margin: 0 1px;
            animation: aiDot 1s infinite ease-in-out;
        }

        .ai-dots span:nth-child(2) {
            animation-delay: .15s;
        }

        .ai-dots span:nth-child(3) {
            animation-delay: .30s;
        }

        @keyframes aiDot {

            0%,
            80%,
            100% {
                transform: translateY(0);
                opacity: .35;
            }

            40% {
                transform: translateY(-3px);
                opacity: 1;
            }
        }

        .ai-model-dropdown {
            min-width: 200px;
        }

        .ai-filter-dropdown {
            min-width: 220px;
        }

        .ai-popup-error,
        .ai-fullscreen-error {
            padding: 12px;
            margin-bottom: 12px;
            border-radius: 6px;
        }

        .ai-popup-error .alert,
        .ai-fullscreen-error .alert {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            padding: 12px;
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .ai-popup {
                width: calc(100vw - 32px);
                height: calc(100vh - 80px);
                right: 16px;
                bottom: 16px;
            }

            .ai-orb-container {
                bottom: 16px;
                right: 16px;
            }
        }
    </style>

    <script>
        const __scrollCampaignAiBottom = () => {
            const popup = document.getElementById('campaignAiBody');
            const full = document.getElementById('campaignAiBodyFull');
            const target = popup || full;

            if (!target) return;

            requestAnimationFrame(() => {
                target.scrollTop = target.scrollHeight;
            });
        };

        document.addEventListener('livewire:initialized', () => {
            // Scroll on explicit event
            Livewire.on('campaign-assistant-scroll-bottom', () => __scrollCampaignAiBottom());

            // Scroll when component updates (new messages)
            Livewire.hook('effect.processed', () => {
                setTimeout(() => __scrollCampaignAiBottom(), 50);
            });

            // Scroll on component first load
            setTimeout(() => __scrollCampaignAiBottom(), 100);

            // Watch for DOM changes and scroll to bottom
            const popup = document.getElementById('campaignAiBody');
            const full = document.getElementById('campaignAiBodyFull');
            const target = popup || full;

            if (target) {
                const observer = new MutationObserver(() => {
                    __scrollCampaignAiBottom();
                });

                observer.observe(target, {
                    childList: true,
                    subtree: true
                });
            }
        });
    </script>
</div>
