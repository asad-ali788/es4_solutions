<div class="ai-app d-flex" wire:key="ai-shell">

    {{-- Mobile overlay (only when sidebar open) --}}
    <button type="button" class="ai-overlay" wire:click="closeSidebar" aria-label="Close sidebar" @class(['d-none' => !$sidebarOpen])>
    </button>

    {{-- Sidebar --}}
    <aside @class(['ai-sidebar', 'is-open' => $sidebarOpen])>
        <div class="ai-sidebar-inner">

            {{-- Brand (robot head toggles sidebar) --}}
            <div class="ai-brand" wire:ignore>
                <button type="button" class="ai-brand-icon-btn" wire:click="toggleSidebar" wire:loading.attr="disabled"
                    title="{{ $sidebarOpen ? 'Hide sidebar' : 'Show sidebar' }}">
                    <span class="ai-brand-icon">
                        <x-ai.logo width="34" height="34" />
                    </span>
                </button>

                <div class="ai-brand-text">
                    <div class="ai-brand-title">Itrend AI</div>
                    <div class="ai-brand-sub text-muted">Business Intelligence</div>
                </div>
            </div>

            {{-- Recent chats --}}
            <div class="ai-recents">
                <div class="ai-recents-head">
                    <div class="ai-recents-title">Recent chats</div>
                    <button type="button" class="btn btn-sm btn-light ai-newchat" wire:click="newChat"
                        wire:loading.attr="disabled" title="New chat">
                        <i class="bx bx-plus"></i>
                    </button>
                </div>

                <div class="ai-recents-list">
                    @forelse ($conversations as $c)
                        @php $active = ($conversationId === $c['id']); @endphp

                        <div class="ai-chat-item {{ $active ? 'active' : '' }}">
                            <button type="button" class="ai-chat-item-btn" wire:click="selectConversation('{{ $c['id'] }}')"
                                wire:loading.attr="disabled" title="{{ $c['title'] }}">
                                <div class="ai-chat-item-title">{{ $c['title'] }}</div>
                            </button>

                            <button type="button" class="ai-chat-item-del" wire:click="deleteConversation('{{ $c['id'] }}')"
                                wire:loading.attr="disabled" title="Delete">
                                <i class="bx bx-trash"></i>
                            </button>
                        </div>
                    @empty
                        <div class="ai-recents-empty">No chats yet.</div>
                    @endforelse
                </div>
            </div>
            @if ($betaNoticeVisible)
                <div class="ai-beta-card" role="note" aria-label="AI Beta warning">
                    <button type="button" class="ai-beta-close" wire:click="closeBetaNotice" title="Close notice">
                        <i class="bx bx-x"></i>
                    </button>
                    <div class="ai-beta-content">
                        <div class="ai-beta-title">
                            <i class='bx bx-error-circle'></i>
                            <span>AI Beta</span>
                        </div>

                        <div class="ai-beta-text">
                            AI-generated content may contain errors or incomplete information. Please validate all
                            critical data before proceeding. </div>
                    </div>
                </div>
            @endif

        </div>
    </aside>

    {{-- Main --}}
    <main class="ai-main flex-grow-1" style="display: flex; flex-direction: column; height: 100%; overflow: hidden;">

        {{-- Top bar --}}
        <div class="ai-topbar" style="flex-shrink: 0;">
            <div class="d-flex align-items-center gap-2">

                {{-- Always-visible toggle (hamburger) --}}
                <button type="button" class="btn btn-sm ai-icon-btn" wire:click="toggleSidebar"
                    wire:loading.attr="disabled" title="{{ $sidebarOpen ? 'Hide sidebar' : 'Show sidebar' }}">
                    <i class="bx {{ $sidebarOpen ? 'bx-menu-alt-left' : 'bx-menu' }}"></i>
                </button>

                {{-- Model dropdown --}}
                <div class="ai-agent">
                    <div class="dropdown">
                        <button class="btn btn-sm ai-agent-btn dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            {{ $selectedModelKey }}
                        </button>

                        <div class="dropdown-menu ai-agent-menu">
                            @foreach ($modelOptions as $label => $cfg)
                                <button class="dropdown-item {{ $selectedModelKey === $label ? 'active' : '' }}"
                                    type="button" wire:click="$set('selectedModelKey', '{{ $label }}')">
                                    {{ $label }}
                                    @if (isset($cfg['accuracy']))
                                        <span class="ai-model-tag ai-model-tag-{{ $cfg['accuracy'] }}"
                                            title="{{ ucfirst($cfg['accuracy']) }} accuracy">{{ ucfirst($cfg['accuracy']) }}</span>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="ai-user d-flex align-items-center gap-3" wire:ignore>
                <div class="ai-user-name text-muted d-none d-sm-block">AI can make mistakes</div>

                <div>
                    <button class="btn btn-sm ai-icon-btn px-2" type="button"
                        data-bs-toggle="modal" data-bs-target="#aiCapabilitiesModal"
                        title="AI Information Center">
                        <i class="bx bx-info-circle fs-5"></i>
                    </button>
                </div>
            </div>
        </div>

        @if ($error)
            <div class="ai-error-banner" role="alert">
                <div class="ai-error-header">
                    <div class="ai-error-summary-row">
                        <span class="ai-error-icon"><i class="bx bx-error-circle"></i></span>
                        <span class="ai-error-title">Something went wrong</span>
                    </div>
                    <div class="ai-error-actions">
                        <details class="ai-error-accordion">
                            <summary class="ai-error-accordion-toggle">
                                <i class="bx bx-chevron-down ai-error-chevron"></i>
                                <span>Details</span>
                            </summary>
                            <pre class="ai-error-detail-pre">{{ $error }}</pre>
                        </details>
                        <button type="button" class="ai-error-reset-btn" wire:click="retryLastMessage"
                            title="Retry last message">
                            <i class="bx bx-revision"></i>
                            <span>Retry</span>
                        </button>
                    </div>
                </div>
            </div>
        @endif

        {{-- Body --}}
        <div class="ai-body" id="aiBody" style="flex: 1; min-height: 0; overflow-y: auto; overflow-x: hidden;">

            {{-- Empty state --}}
            @php 
                $hasChat = !empty($messages) || $question || $answer;
            @endphp

            {{-- Persistent Greeting --}}
            <div class="ai-greeting {{ $hasChat ? 'is-collapsed' : '' }}">
                <div class="ai-greeting-title">
                    👋 <span class="ai-gradient"> Hello {{ auth()->user()->name ?? 'there' }}</span>
                </div>
                @if (!$hasChat)
                    <div class="ai-greeting-sub">✨ How can I help you today?</div>
                @endif
            </div>

            {{-- Suggestions Grid (only shown when no chat) --}}
            @if (!$hasChat)
                <div class="ai-suggest" wire:ignore>
                    @php
                        $suggestions = [
                            ['icon' => 'bx-trophy', 'title' => '🏆 Top Selling', 'text' => 'What were my top selling products yesterday?', 'prompt' => 'What were my top selling products yesterday? (last available data)'],
                            ['icon' => 'bx-trending-up', 'title' => '📈 Rank Tracking', 'text' => 'Check my latest keyword ranks for my top ASINs.', 'prompt' => 'Check my latest keyword ranks for my top ASINs.'],
                            ['icon' => 'bx-search-alt', 'title' => '🔍 Search Terms', 'text' => 'Find new high-performing search terms from the last 7 days.', 'prompt' => 'Find new search terms with sales that are not yet keywords from the last 7 days.'],
                            ['icon' => 'bx-dollar', 'title' => '💰 Bidding Tips', 'text' => 'Show me keyword bid recommendations for my active campaigns.', 'prompt' => 'Show me AWS keyword bid recommendations for my active campaigns.'],
                            ['icon' => 'bx-error-circle', 'title' => '⚠️ Performance', 'text' => 'Which campaigns are wasting spend or have a high ACOS lately?', 'prompt' => 'Which campaigns are wasting spend or have a high ACOS lately?'],
                            ['icon' => 'bx-package', 'title' => '📦 Stock Health', 'text' => 'Show my current warehouse stock levels and highlight low-stock items.', 'prompt' => 'Show my current warehouse stock levels and highlight anything running low.'],
                            ['icon' => 'bx-time-five', 'title' => '🕒 Hourly Sales', 'text' => 'Get a breakdown of today\'s sales by the hour.', 'prompt' => 'Give me a breakdown of hourly sales for today.'],
                            ['icon' => 'bx-line-chart', 'title' => '💎 Brand Analysis', 'text' => 'Analyze top brand search terms and market basket.', 'prompt' => 'List top 5 asin which have strong brand analytics'],
                        ];
                    @endphp

                    @foreach ($suggestions as $s)
                        <button type="button" class="ai-suggest-card"
                            onclick="window.__setAiPrompt('{{ addslashes($s['prompt']) }}')">
                            <div class="ai-suggest-icon"><i class="bx {{ $s['icon'] }}"></i></div>
                            <div class="ai-suggest-title">{{ $s['title'] }}</div>
                            <div class="ai-suggest-text">
                                {{ $s['text'] }}
                            </div>
                        </button>
                    @endforeach
                </div>
            @endif

            {{-- Messages --}}
            <div class="ai-thread">
                @foreach ($messages as $index => $m)
                    @if (($m['role'] ?? '') === 'user')
                        <div class="ai-msg ai-msg-user" wire:key="msg-user-{{ $index }}">
                            <div class="ai-bubble">
                                <div style="white-space: pre-wrap;">{{ $m['content'] }}</div>
                            </div>
                        </div>
                    @else
                        <div class="ai-msg ai-msg-bot" wire:key="msg-bot-{{ $index }}">
                            <div class="ai-avatar">
                                <x-ai.logo width="25" height="25" />
                            </div>
                            <div class="ai-bubble {{ ($m['is_html'] ?? false) === true ? 'ai-bubble-rich' : '' }}">
                                @if(!empty($m['reasoning']))
                                    <details class="ai-reasoning-block">
                                        <summary class="ai-reasoning-summary">
                                            <i class="bx bx-brain"></i> Reasoning
                                            <i class="bx bx-chevron-down ai-reasoning-chevron"></i>
                                        </summary>
                                        <div class="ai-reasoning-content">{{ $m['reasoning'] }}</div>
                                    </details>
                                @endif
                                @if (($m['is_html'] ?? false) === true)
                                    <div class="ai-rich-content">
                                        {!! $m['content'] !!}
                                    </div>
                                @else
                                    <div style="white-space: pre-wrap;">{{ $m['content'] }}</div>
                                @endif

                                {{-- Excel download button --}}
                                @if(($m['role'] ?? '') === 'assistant' && !empty($m['trace_id']))
                                    <div class="ai-msg-actions mt-3 pt-2 border-top border-opacity-10 d-flex justify-content-end">
                                        <a href="{{ route('admin.ai.export', ['trace_id' => $m['trace_id']]) }}" target="_blank"
                                            class="ai-export-btn d-inline-flex align-items-center gap-2"
                                            title="Download this result as Excel">
                                            <div class="ai-export-icon">
                                                <i class='bx bxs-file-export'></i>
                                            </div>
                                            <div class="ai-export-text">
                                                <span class="ai-export-label">Download Report</span>
                                                <span class="ai-export-ext">.XLSX</span>
                                            </div>
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                @endforeach

                {{-- Streaming in-progress --}}
                @if ($question)
                    <div class="ai-msg ai-msg-user">
                        <div class="ai-bubble">
                            <div style="white-space: pre-wrap;">{{ $question }}</div>
                        </div>
                    </div>

                    <div class="ai-msg ai-msg-bot ai-msg-bot-stream">
                        <div class="ai-msg-bot-main">
                            <div class="ai-avatar"><x-ai.logo width="25" height="25" /></div>
                            <div class="ai-bubble">
                                {{-- target must match the method name that runs --}}
                                <div class="ai-thinking-modern" wire:loading wire:target="askStream">
                                    <div class="ai-thinking-active">
                                        <i class="bx bx-loader bx-spin ai-spin-icon"></i>
                                        <div class="ai-stream-ctx" id="aiStreamContext">💡 Preparing analysis...</div>
                                    </div>
                                    <div class="ai-thinking-steps" id="aiThinkingSteps">
                                        <div class="ai-thinking-step is-visible">🔎 Understanding your question</div>
                                    </div>
                                </div>

                                {{-- Live markdown streaming with marked.js --}}
                                <div x-data="{
                                                                    parsedAnswer: '',
                                                                    sanitize(text) {
                                                                        text = text.replace(/<think[^>]*>[\s\S]*?<\/think>/gi, '');
                                                                        text = text.replace(/<think[^>]*>[\s\S]*$/gi, '');
                                                                        return text.trim();
                                                                    },
                                                                    init() {
                                                                        const ob = new MutationObserver(() => {
                                                                            const raw = this.$refs.rawStreamAnswer.innerText;
                                                                            const clean = this.sanitize(raw);
                                                                            if (clean.length > 0) {
                                                                                if (typeof marked !== 'undefined') {
                                                                                    marked.setOptions({ breaks: true });
                                                                                    this.parsedAnswer = marked.parse(clean);
                                                                                } else {
                                                                                    this.parsedAnswer = clean.replace(/\n/g, '<br>');
                                                                                }
                                                                                window.dispatchEvent(new CustomEvent('ai-scroll-bottom'));
                                                                            }
                                                                        });
                                                                        ob.observe(this.$refs.rawStreamAnswer, { childList: true, characterData: true, subtree: true });
                                                                    }
                                                                }">

                                    <div x-show="parsedAnswer.length > 0" x-html="parsedAnswer" class="ai-rich-content"
                                        style="margin-top: 10px;"></div>

                                    {{-- Hidden wire:stream target --}}
                                    <div x-ref="rawStreamAnswer" id="aiStreamHiddenAnswer" class="ai-stream-hidden"
                                        wire:stream="answer">{{ $answer }}</div>
                                </div>
                                <div id="aiProgressHidden" class="ai-stream-hidden" wire:stream="progress"></div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Composer --}}
        <div class="ai-composer" style="flex: 0 0 auto; min-width: 0; overflow: visible;">
            <form wire:submit.prevent="submitPrompt" class="ai-composer-inner"
                style="display: flex; gap: 12px; align-items: flex-end; width: 100%;">
                <textarea id="aiPrompt" class="form-control" wire:model.defer="prompt"
                    wire:keydown.ctrl.enter.prevent="submitPrompt" rows="2"
                    placeholder="Ask something... (Ctrl + Enter to submit)"
                    style="flex: 1; padding: 10px 14px; border-radius: 12px; resize: none; min-height: 44px;"></textarea>

                <button class="btn ai-send" type="submit" wire:loading.attr="disabled"
                    style="width: 44px; height: 44px; flex-shrink: 0;">
                    <span wire:loading.remove wire:target="submitPrompt"><i class="bx bx-send"></i></span>
                    <span wire:loading wire:target="submitPrompt"><i class="bx bx-loader bx-spin"></i></span>
                </button>
            </form>
        </div>
    </main>

    <div wire:ignore>
        @include('components.ai.capabilities-modal')
    </div>

    {{-- (CSS unchanged from your version) --}}
    <style>
        /* Hide footer on AI pages */
        footer,
        .footer {
            display: none !important;
        }

        /* Remove padding from page-content for full-height AI app */
        .page-content {
            padding-bottom: 0 !important;
        }

        /* Mobile viewport adjustments */
        @@supports (-webkit-touch-callout: none) {

            /* iOS Safari specific */
            .ai-app {
                height: calc(100vh - 100px);
                max-height: -webkit-fill-available;
            }

            .ai-main {
                height: 100% !important;
                max-height: 100%;
            }
        }

        /* Safe area insets for notched devices */
        @@supports (padding: max(0px)) {
            .ai-composer {
                padding-bottom: max(12px, env(safe-area-inset-bottom));
            }
        }

        .ai-beta-card {
            position: relative;
            padding: 8px 10px 8px 10px;
            border-radius: 10px;
            background: linear-gradient(180deg,
                    rgba(239, 68, 68, .20) 0%,
                    rgba(248, 113, 113, .10) 35%,
                    rgba(255, 255, 255, .94) 100%);
            border: 1px solid rgba(59, 130, 246, .25);
            box-shadow: 0 4px 12px rgba(15, 23, 42, .06);
            transition: all 0.2s ease;
            padding-right: 28px;
        }

        /* Soft hover lift */
        .ai-beta-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(15, 23, 42, .08);
        }

        .ai-beta-close {
            position: absolute;
            top: 6px;
            right: 6px;
            background: transparent;
            border: 0;
            padding: 4px;
            cursor: pointer;
            color: var(--ai-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            line-height: 1;
            transition: all 0.2s ease;
        }

        .ai-beta-close:hover {
            color: var(--ai-text);
            transform: scale(1.1);
        }

        .ai-beta-content {
            position: relative;
            z-index: 2;
        }

        .ai-beta-title {
            font-weight: 600;
            font-size: 12px;
            margin-bottom: 3px;
            display: flex;
            align-items: center;
            gap: 5px;
            color: var(--ai-text);
        }

        .ai-beta-title i {
            font-size: 13px;
            color: #2563eb;
            flex-shrink: 0;
        }

        .ai-beta-text {
            font-size: 11px;
            line-height: 1.3;
            color: var(--ai-muted);
        }

        html[data-bs-theme="dark"] .ai-beta-card {
            background: linear-gradient(180deg, rgba(37, 99, 235, .30) 0%, rgba(59, 130, 246, .16) 34%, rgba(15, 23, 42, .90) 100%);
            border-color: rgba(147, 197, 253, .20);
            box-shadow: 0 4px 12px rgba(0, 0, 0, .25);
        }

        html[data-bs-theme="dark"] .ai-beta-card:hover {
            box-shadow: 0 6px 16px rgba(0, 0, 0, .30);
        }

        html[data-bs-theme="dark"] .ai-beta-title i {
            color: #93c5fd;
        }

        /* ============================================
           CORE DESIGN TOKENS & VARIABLES
           ============================================ */
        :root {
            /* Colors */
            --ai-bg: #ffffff;
            --ai-surface: #ffffff;
            --ai-muted: #6b7280;
            --ai-text: #0f172a;
            --ai-border: #e5e7eb;
            --ai-soft: #f4f6fb;
            --ai-shadow: 0 8px 30px rgba(15, 23, 42, .06);
            --ai-primary: #2563eb;

            /* Dimensions */
            --ai-sidebar-width: 270px;
            --ai-topbar-height: 52px;
            --ai-content-max-width: 820px;
            --ai-content-padding: 16px;
            --ai-content-gap: 10px;

            /* Spacing */
            --ai-xs: 4px;
            --ai-sm: 8px;
            --ai-md: 12px;
            --ai-lg: 16px;
            --ai-xl: 20px;
            --ai-2xl: 26px;

            /* Border Radius */
            --ai-radius-sm: 10px;
            --ai-radius-md: 12px;
            --ai-radius-lg: 14px;
            --ai-radius-xl: 18px;
        }

        /* Excel Download Button Styling */
        .ai-export-btn {
            background: linear-gradient(135deg, rgba(46, 125, 50, 0.08), rgba(46, 125, 50, 0.04));
            border: 1px solid rgba(46, 125, 50, 0.15);
            border-radius: var(--ai-radius-md);
            padding: 6px 12px;
            text-decoration: none !important;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            color: #2e7d32 !important;
            box-shadow: 0 2px 8px rgba(46, 125, 50, 0.04);
        }

        .ai-export-btn:hover {
            background: linear-gradient(135deg, rgba(46, 125, 50, 0.12), rgba(46, 125, 50, 0.08));
            border-color: rgba(46, 125, 50, 0.3);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(46, 125, 50, 0.08);
        }

        .ai-export-btn:active {
            transform: translateY(0);
        }

        .ai-export-icon {
            font-size: 18px;
            display: flex;
            align-items: center;
        }

        .ai-export-text {
            display: flex;
            flex-direction: column;
            line-height: 1.1;
        }

        .ai-export-label {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .ai-export-ext {
            font-size: 8px;
            font-weight: 800;
            opacity: 0.7;
            margin-top: 1px;
        }

        html[data-bs-theme="dark"] .ai-export-btn {
            background: linear-gradient(135deg, rgba(74, 222, 128, 0.08), rgba(74, 222, 128, 0.04));
            border-color: rgba(74, 222, 128, 0.2);
            color: #4ade80 !important;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        html[data-bs-theme="dark"] .ai-export-btn:hover {
            background: linear-gradient(135deg, rgba(74, 222, 128, 0.15), rgba(74, 222, 128, 0.1));
            border-color: rgba(74, 222, 128, 0.4);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        html[data-bs-theme="dark"] {
            --ai-bg: #0b1220;
            --ai-surface: #0f172a;
            --ai-muted: #9ca3af;
            --ai-text: #e5e7eb;
            --ai-border: rgba(255, 255, 255, .08);
            --ai-soft: rgba(255, 255, 255, .04);
            --ai-shadow: 0 12px 30px rgba(0, 0, 0, .35);
        }

        html {
            scrollbar-gutter: stable;
        }

        /* ============================================
           MAIN LAYOUT & CONTAINER
           ============================================ */
        .ai-app {
            display: flex;
            height: calc(100vh - 100px);
            border-radius: var(--ai-radius-lg);
            overflow: hidden;
            background: var(--ai-bg);
            border: 1px solid var(--ai-border);
            position: relative;
            flex-shrink: 0;
            box-sizing: border-box;
        }

        * {
            box-sizing: border-box;
        }

        .ai-overlay {
            display: none;
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, .35);
            border: 0;
            z-index: 30;
        }

        /* ============================================
           SIDEBAR
           ============================================ */
        .ai-sidebar {
            width: var(--ai-sidebar-width);
            background: var(--ai-surface);
            border-right: 1px solid var(--ai-border);
            transition: width .18s ease, transform .18s ease;
            flex-shrink: 0;
        }

        .ai-sidebar-inner {
            height: 100%;
            display: flex;
            flex-direction: column;
            padding: var(--ai-md);
            gap: var(--ai-md);
        }

        .ai-brand {
            display: flex;
            align-items: center;
            gap: var(--ai-md);
            padding: var(--ai-sm) var(--ai-xs);
            border-radius: var(--ai-radius-md);
            flex-shrink: 0;
        }

        .ai-brand-text {
            min-width: 0;
            flex: 1;
        }

        .ai-brand-title {
            font-weight: 800;
            color: var(--ai-text);
            line-height: 1.1;
            font-size: 15px;
        }

        .ai-brand-sub {
            font-size: 12px;
            color: var(--ai-muted);
        }

        .ai-brand-icon-btn {
            border: 0;
            padding: 0;
            background: transparent;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            flex-shrink: 0;
        }

        .ai-brand-icon-btn:focus {
            outline: none;
        }

        .ai-brand-icon {
            width: 34px;
            height: 34px;
            border-radius: var(--ai-radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(37, 99, 235, .18), rgba(59, 130, 246, .14));
            border: 1px solid var(--ai-border);
            flex-shrink: 0;
        }

        .ai-brand-icon i {
            font-size: 18px;
            color: var(--ai-primary);
        }

        /* ============================================
           RECENTS & CHAT LIST
           ============================================ */
        .ai-recents {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
            gap: var(--ai-sm);
        }

        .ai-recents-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: var(--ai-xs);
            flex-shrink: 0;
        }

        .ai-recents-title {
            font-size: 10px;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--ai-muted);
            font-weight: 600;
        }

        .ai-newchat {
            border-radius: var(--ai-radius-sm);
            padding: var(--ai-xs) var(--ai-sm);
            background: var(--ai-soft);
            border: 1px solid var(--ai-border);
            color: var(--ai-text);
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .ai-newchat:hover {
            background: var(--ai-border);
        }

        .ai-recents-list {
            overflow: auto;
            padding: var(--ai-xs);
            display: flex;
            flex-direction: column;
            gap: 5px;
            flex: 1;
            min-height: 0;
        }

        .ai-chat-item {
            display: flex;
            gap: var(--ai-xs);
            align-items: stretch;
            border: 1px solid var(--ai-border);
            border-radius: var(--ai-radius-md);
            flex-shrink: 0;
        }

        .ai-chat-item.active {
            background: linear-gradient(135deg, rgba(37, 99, 235, .12), rgba(59, 130, 246, .08));
            border-color: rgba(59, 130, 246, .35);
        }

        .ai-chat-item-btn {
            flex: 1;
            text-align: left;
            background: transparent;
            border: 0;
            padding: var(--ai-md);
            border-radius: var(--ai-radius-md);
            color: var(--ai-text);
            cursor: pointer;
            transition: all 0.15s ease;
            overflow: hidden;
        }

        .ai-chat-item-btn:hover {
            background: var(--ai-soft);
        }

        .ai-chat-item-title {
            font-weight: 600;
            font-size: 11px;
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .ai-chat-item-del {
            width: 36px;
            border: 0;
            border-radius: var(--ai-radius-md);
            background: transparent;
            color: var(--ai-muted);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s ease;
        }

        .ai-chat-item-del:hover {
            background: var(--ai-soft);
            color: #ef4444;
        }

        .ai-recents-empty {
            color: var(--ai-muted);
            font-size: 12px;
            padding: var(--ai-md) var(--ai-sm);
            text-align: center;
        }

        /* ============================================
           BETA NOTICE
           ============================================ */
        .ai-beta-card {
            position: relative;
            padding: var(--ai-md);
            border-radius: var(--ai-radius-md);
            background: linear-gradient(180deg,
                    rgba(239, 68, 68, .20) 0%,
                    rgba(248, 113, 113, .10) 35%,
                    rgba(255, 255, 255, .94) 100%);
            border: 1px solid rgba(59, 130, 246, .25);
            box-shadow: 0 4px 12px rgba(15, 23, 42, .06);
            transition: all 0.2s ease;
            padding-right: 28px;
            flex-shrink: 0;
        }

        .ai-beta-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(15, 23, 42, .08);
        }

        .ai-beta-close {
            position: absolute;
            top: 6px;
            right: 6px;
            background: transparent;
            border: 0;
            padding: var(--ai-xs);
            cursor: pointer;
            color: var(--ai-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            line-height: 1;
            transition: all 0.2s ease;
        }

        .ai-beta-close:hover {
            color: var(--ai-text);
            transform: scale(1.1);
        }

        .ai-beta-content {
            position: relative;
            z-index: 2;
        }

        .ai-beta-title {
            font-weight: 600;
            font-size: 12px;
            margin-bottom: var(--ai-xs);
            display: flex;
            align-items: center;
            gap: 5px;
            color: var(--ai-text);
        }

        .ai-beta-title i {
            font-size: 13px;
            color: var(--ai-primary);
            flex-shrink: 0;
        }

        .ai-beta-text {
            font-size: 11px;
            line-height: 1.3;
            color: var(--ai-muted);
        }

        html[data-bs-theme="dark"] .ai-beta-card {
            background: linear-gradient(180deg, rgba(37, 99, 235, .30) 0%, rgba(59, 130, 246, .16) 34%, rgba(15, 23, 42, .90) 100%);
            border-color: rgba(147, 197, 253, .20);
            box-shadow: 0 4px 12px rgba(0, 0, 0, .25);
        }

        html[data-bs-theme="dark"] .ai-beta-card:hover {
            box-shadow: 0 6px 16px rgba(0, 0, 0, .30);
        }

        html[data-bs-theme="dark"] .ai-beta-title i {
            color: #93c5fd;
        }

        html[data-bs-theme="dark"] .ai-beta-text {
            color: rgba(246, 217, 223, 0.85);
        }



        /* ============================================
           MAIN CONTENT AREA
           ============================================ */
        .ai-main {
            display: flex;
            flex-direction: column;
            background: var(--ai-bg);
            min-width: 0;
            flex: 1;
            position: relative;
            height: 100%;
            overflow: hidden !important;
        }

        .ai-topbar {
            height: var(--ai-topbar-height);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 var(--ai-lg);
            border-bottom: 1px solid var(--ai-border);
            background: var(--ai-surface);
            flex-shrink: 0;
            z-index: 100;
        }

        .ai-icon-btn {
            width: 38px;
            height: 38px;
            border-radius: var(--ai-radius-md);
            border: 1px solid var(--ai-border);
            background: var(--ai-soft);
            color: var(--ai-text);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .ai-icon-btn:hover {
            filter: brightness(1.02);
        }

        .ai-agent-btn {
            border-radius: var(--ai-radius-md);
            border: 1px solid var(--ai-border);
            background: var(--ai-soft);
            color: var(--ai-text);
            padding: var(--ai-sm) var(--ai-md);
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .ai-agent-menu {
            border-radius: var(--ai-radius-lg);
            border: 1px solid var(--ai-border);
            background: var(--ai-surface);
            box-shadow: var(--ai-shadow);
            overflow: hidden;
        }

        .ai-agent-menu .dropdown-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: var(--ai-md);
        }

        .ai-model-tag {
            font-size: 10px;
            line-height: 1;
            padding: var(--ai-xs) var(--ai-sm);
            border-radius: 999px;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .ai-model-tag-high {
            border: 1px solid rgba(16, 185, 129, .35);
            color: #059669;
            background: rgba(16, 185, 129, .1);
        }

        .ai-model-tag-medium {
            border: 1px solid rgba(59, 130, 246, .35);
            color: #2563eb;
            background: rgba(59, 130, 246, .1);
        }

        .ai-model-tag-low {
            border: 1px solid rgba(245, 158, 11, .35);
            color: #d97706;
            background: rgba(245, 158, 11, .1);
        }

        html[data-bs-theme="dark"] .ai-model-tag-high {
            color: #6ee7b7;
            border-color: rgba(110, 231, 183, .4);
            background: rgba(16, 185, 129, .18);
        }

        html[data-bs-theme="dark"] .ai-model-tag-medium {
            color: #93c5fd;
            border-color: rgba(147, 197, 253, .4);
            background: rgba(59, 130, 246, .18);
        }

        html[data-bs-theme="dark"] .ai-model-tag-low {
            color: #fcd34d;
            border-color: rgba(252, 211, 77, .4);
            background: rgba(245, 158, 11, .18);
        }

        .ai-user {
            display: flex;
            align-items: center;
            gap: var(--ai-md);
        }

        .ai-user-name {
            font-size: 12px;
            color: var(--ai-muted);
        }

        /* ============================================
           ERROR BANNER
           ============================================ */
        .ai-error-banner {
            margin: var(--ai-md) var(--ai-lg) 0;
            border: 1px solid rgba(239, 68, 68, .35);
            border-radius: var(--ai-radius-md);
            background: linear-gradient(135deg, rgba(254, 242, 242, .95), rgba(255, 255, 255, .90));
            box-shadow: 0 2px 10px rgba(239, 68, 68, .08);
            overflow: hidden;
            flex-shrink: 0;
        }

        html[data-bs-theme="dark"] .ai-error-banner {
            background: linear-gradient(135deg, rgba(127, 29, 29, .40), rgba(15, 23, 42, .85));
            border-color: rgba(239, 68, 68, .30);
        }

        .ai-error-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: var(--ai-sm);
            padding: 10px var(--ai-md);
        }

        .ai-error-summary-row {
            display: flex;
            align-items: center;
            gap: var(--ai-sm);
        }

        .ai-error-icon {
            font-size: 17px;
            color: #dc2626;
            line-height: 1;
            display: flex;
        }

        html[data-bs-theme="dark"] .ai-error-icon {
            color: #fca5a5;
        }

        .ai-error-title {
            font-size: 13px;
            font-weight: 700;
            color: #dc2626;
        }

        html[data-bs-theme="dark"] .ai-error-title {
            color: #fca5a5;
        }

        .ai-error-actions {
            display: flex;
            align-items: center;
            gap: var(--ai-sm);
            flex-wrap: wrap;
        }

        .ai-error-accordion {
            font-size: 12px;
        }

        .ai-error-accordion-toggle {
            display: flex;
            align-items: center;
            gap: 4px;
            cursor: pointer;
            list-style: none;
            padding: 5px 10px;
            border-radius: var(--ai-radius-sm);
            border: 1px solid rgba(239, 68, 68, .30);
            background: rgba(239, 68, 68, .08);
            color: #b91c1c;
            font-weight: 600;
            user-select: none;
            transition: background .15s ease, border-color .15s ease;
        }

        .ai-error-accordion-toggle::-webkit-details-marker {
            display: none;
        }

        .ai-error-accordion-toggle:hover {
            background: rgba(239, 68, 68, .14);
            border-color: rgba(239, 68, 68, .45);
        }

        html[data-bs-theme="dark"] .ai-error-accordion-toggle {
            color: #fca5a5;
            background: rgba(239, 68, 68, .12);
            border-color: rgba(239, 68, 68, .25);
        }

        .ai-error-chevron {
            transition: transform .18s ease;
            font-size: 14px;
        }

        .ai-error-accordion[open] .ai-error-chevron {
            transform: rotate(180deg);
        }

        .ai-error-detail-pre {
            margin: 0;
            padding: 10px var(--ai-md) var(--ai-md);
            font-size: 11px;
            white-space: pre-wrap;
            word-break: break-all;
            color: #7f1d1d;
            background: rgba(254, 226, 226, .50);
            border-top: 1px solid rgba(239, 68, 68, .20);
        }

        html[data-bs-theme="dark"] .ai-error-detail-pre {
            color: #fca5a5;
            background: rgba(127, 29, 29, .25);
            border-top-color: rgba(239, 68, 68, .20);
        }

        .ai-error-reset-btn {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            font-size: 12px;
            font-weight: 600;
            border-radius: var(--ai-radius-sm);
            border: 1px solid rgba(37, 99, 235, .35);
            background: rgba(37, 99, 235, .10);
            color: var(--ai-primary);
            cursor: pointer;
            transition: background .15s ease, border-color .15s ease;
        }

        .ai-error-reset-btn:hover {
            background: rgba(37, 99, 235, .18);
            border-color: rgba(37, 99, 235, .55);
        }

        html[data-bs-theme="dark"] .ai-error-reset-btn {
            color: #93c5fd;
            border-color: rgba(147, 197, 253, .30);
            background: rgba(59, 130, 246, .12);
        }

        html[data-bs-theme="dark"] .ai-error-reset-btn:hover {
            background: rgba(59, 130, 246, .22);
            border-color: rgba(147, 197, 253, .50);
        }

        /* ============================================
           CHAT BODY & MESSAGE AREA
           ============================================ */
        .ai-body {
            flex: 1 1 auto;
            min-height: 0 !important;
            min-width: 0;
            overflow-y: auto !important;
            overflow-x: hidden !important;
            padding: var(--ai-lg);
            background: radial-gradient(1000px 400px at 100% 0%, rgba(59, 130, 246, .08), transparent 60%),
                radial-gradient(1000px 400px at 0% 0%, rgba(37, 99, 235, .06), transparent 60%),
                var(--ai-bg);
            position: relative;
            z-index: 1;
            width: 100%;
        }

        .ai-greeting {
            max-width: 920px;
            margin: 60px auto 0;
            text-align: center;
            width: 100%;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .ai-greeting.is-collapsed {
            margin: 0 0 20px 0;
            text-align: left;
            max-width: 100%;
        }

        .ai-greeting-title {
            font-size: 42px;
            font-weight: 800;
            margin-bottom: var(--ai-sm);
            color: var(--ai-text);
            transition: all 0.3s ease;
        }

        .ai-greeting.is-collapsed .ai-greeting-title {
            font-size: 24px;
            margin-bottom: 0;
        }

        .ai-gradient {
            background: linear-gradient(90deg, #1d4ed8 0%, #3b82f6 40%, #6366f1 70%, #8b5cf6 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .ai-greeting-sub {
            font-size: 26px;
            color: rgba(107, 114, 128, .75);
            margin-bottom: var(--ai-2xl);
        }

        html[data-bs-theme="dark"] .ai-greeting-sub {
            color: rgba(156, 163, 175, .8);
        }

        /* ============================================
           SUGGESTION CARDS
           ============================================ */
        .ai-suggest {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
            margin-top: var(--ai-xl);
            padding: 0 var(--ai-lg);
        }



        .ai-suggest-card {
            text-align: left;
            border-radius: var(--ai-radius-md);
            border: 1px solid var(--ai-border);
            background: rgba(255, 255, 255, .70);
            padding: 14px;
            box-shadow: 0 4px 10px rgba(15, 23, 42, .04);
            transition: all 0.2s ease;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .ai-suggest-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(15, 23, 42, .08);
        }

        html[data-bs-theme="dark"] .ai-suggest-card {
            background: rgba(15, 23, 42, .75);
        }

        .ai-suggest-icon {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--ai-soft);
            border: 1px solid var(--ai-border);
            margin-bottom: 2px;
            color: var(--ai-primary);
            flex-shrink: 0;
            font-size: 14px;
        }

        .ai-suggest-title {
            font-weight: 700;
            color: var(--ai-text);
            margin-bottom: var(--ai-xs);
        }

        .ai-suggest-text {
            color: var(--ai-muted);
            font-size: 12px;
            line-height: 1.4;
        }

        /* ============================================
           THREAD & MESSAGES
           ============================================ */
        .ai-thread {
            display: flex;
            flex-direction: column;
            gap: var(--ai-content-gap);
            width: 100%;
            padding-bottom: var(--ai-lg);
            min-width: 0;
            overflow: visible;
        }

        .ai-msg {
            display: flex;
            gap: var(--ai-content-gap);
            align-items: flex-end;
        }

        .ai-msg-user {
            justify-content: flex-end;
        }

        .ai-msg-user .ai-bubble {
            max-width: min(var(--ai-content-max-width), calc(100% - 40px));
            margin-left: auto;
            margin-right: var(--ai-lg);
        }

        .ai-msg-bot {
            justify-content: flex-start;
        }

        .ai-msg-bot-main {
            display: flex;
            align-items: flex-end;
            gap: var(--ai-md);
            width: 100%;
            min-width: 0;
        }

        .ai-msg-bot-stream {
            width: 100%;
        }

        .ai-avatar {
            width: 34px;
            height: 34px;
            border-radius: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(37, 99, 235, .14), rgba(99, 102, 241, .12));
            border: 1px solid var(--ai-border);
            color: var(--ai-primary);
            flex-shrink: 0;
            box-shadow: 0 6px 16px rgba(37, 99, 235, .12);
        }

        .ai-bubble {
            border-radius: var(--ai-radius-xl);
            padding: var(--ai-md) var(--ai-lg);
            border: 1px solid var(--ai-border);
            background: linear-gradient(180deg, rgba(255, 255, 255, .88), rgba(255, 255, 255, .78));
            color: var(--ai-text);
            box-shadow: var(--ai-shadow);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            transition: transform .18s ease, box-shadow .18s ease;
            overflow: hidden;
            max-width: 100%;
            min-width: 0;
        }

        .ai-msg-bot .ai-bubble {
            flex: 1 1 auto;
            width: 100%;
            max-width: 100%;
            padding: 16px 18px;
            line-height: 1.6;
            overflow: hidden;
            min-width: 0;
            display: flex;
            flex-direction: column;
        }

        .ai-msg-bot-stream .ai-bubble {
            flex: none;
            width: min(760px, 100%);
            max-width: min(760px, 100%);
        }

        .ai-msg-bot .ai-bubble.ai-bubble-rich {
            width: 100%;
            max-width: min(1280px, 100%);
            overflow-x: hidden;
            overflow-y: auto;
            min-width: 0;
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .ai-msg-bot .ai-bubble.ai-bubble-rich .ai-rich-content {
            width: 100%;
            min-width: 0;
            flex: 1;
            overflow-x: auto;
            overflow-y: auto;
        }

        .ai-msg-bot .ai-bubble:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 24px rgba(15, 23, 42, .10);
        }

        html[data-bs-theme="dark"] .ai-bubble {
            background: linear-gradient(180deg, rgba(15, 23, 42, .85), rgba(15, 23, 42, .76));
        }

        .ai-msg-user .ai-bubble {
            border-color: rgba(59, 130, 246, .35);
            background: linear-gradient(135deg, rgba(59, 130, 246, .14), rgba(99, 102, 241, .10));
            box-shadow: 0 10px 22px rgba(37, 99, 235, .12);
        }

        html[data-bs-theme="dark"] .ai-msg-user .ai-bubble {
            background: linear-gradient(135deg, rgba(59, 130, 246, .20), rgba(99, 102, 241, .14));
        }

        /* ============================================
           RICH CONTENT STYLING
           ============================================ */
        .ai-rich-content {
            line-height: 1.55;
            width: 100%;
            max-width: 100%;
            overflow-x: auto;
            overflow-y: visible;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .ai-rich-content>* {
            margin: 0;
            width: 100%;
            max-width: 100%;
            min-width: 0;
        }

        .ai-rich-content>*:last-child {
            margin-bottom: 0;
        }

        .ai-rich-content>*:last-child {
            margin-bottom: 0;
        }

        .ai-rich-content table {
            width: 100%;
            border-collapse: collapse;
            max-width: 100%;
            table-layout: auto;
            min-width: 820px;
        }

        .ai-rich-content table th,
        .ai-rich-content table td {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .ai-rich-content h1,
        .ai-rich-content h2,
        .ai-rich-content h3,
        .ai-rich-content h4,
        .ai-rich-content h5,
        .ai-rich-content h6 {
            margin: 0 0 8px;
            line-height: 1.3;
            font-weight: 700;
        }

        .ai-rich-content ul,
        .ai-rich-content ol {
            padding-left: 20px;
        }

        .ai-rich-content li {
            margin: var(--ai-xs) 0;
        }

        .ai-rich-content blockquote {
            border-left: 3px solid rgba(59, 130, 246, .45);
            padding-left: var(--ai-md);
            color: var(--ai-muted);
        }

        .ai-rich-content pre,
        .ai-rich-content code {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        }

        .ai-rich-content pre {
            border: 1px solid var(--ai-border);
            border-radius: var(--ai-radius-sm);
            padding: var(--ai-md);
            background: var(--ai-soft);
            overflow: auto;
        }

        .ai-rich-content a {
            color: var(--ai-primary);
            text-decoration: underline;
        }

        .ai-rich-content img,
        .ai-rich-content svg {
            display: block;
            max-width: 100%;
            height: auto;
            border-radius: var(--ai-radius-sm);
            border: 1px solid var(--ai-border);
            margin: var(--ai-md) 0;
        }

        .ai-rich-content svg {
            overflow: visible;
        }

        html[data-bs-theme="dark"] .ai-rich-content a {
            color: #93c5fd;
        }

        /* ============================================
           TABLES
           ============================================ */
        .ai-rich-table-wrap {
            width: 100%;
            max-width: 100%;
            min-width: 0;
            overflow-x: auto;
            overflow-y: hidden;
            display: flex;
        }

        .ai-rich-table,
        .ai-bubble table {
            width: 100%;
            min-width: 100%;
            max-width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            border: 1px solid rgba(148, 163, 184, .35);
            border-radius: var(--ai-radius-sm);
            overflow: hidden;
            flex-shrink: 0;
        }

        .ai-rich-table th,
        .ai-rich-table td,
        .ai-bubble table th,
        .ai-bubble table td {
            border: 1px solid rgba(148, 163, 184, .28);
            padding: var(--ai-sm) var(--ai-md);
            text-align: left;
            vertical-align: top;
            word-wrap: break-word;
            word-break: break-word;
            max-width: 200px;
        }

        .ai-rich-table th,
        .ai-bubble table th {
            background: var(--ai-soft);
            font-weight: 700;
            white-space: nowrap;
        }

        /* Table Copy Button */
        .ai-table-wrapper {
            position: relative;
            margin: 12px 0;
        }

        .ai-table-copy-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 6px 10px;
            font-size: 11px;
            font-weight: 600;
            color: #6b7280;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 4px;
            transition: all 0.15s ease;
            z-index: 10;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            opacity: 0;
        }

        .ai-table-wrapper:hover .ai-table-copy-btn {
            opacity: 1;
        }

        .ai-table-copy-btn:hover {
            background: #e5e7eb;
            color: #374151;
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
        }

        .ai-table-copy-btn.copied {
            background: #10b981;
            color: white;
            border-color: #10b981;
            opacity: 1;
        }

        html[data-bs-theme="dark"] .ai-table-copy-btn {
            background: #374151;
            border-color: #4b5563;
            color: #9ca3af;
        }

        html[data-bs-theme="dark"] .ai-table-copy-btn:hover {
            background: #4b5563;
            color: #e5e7eb;
        }

        html[data-bs-theme="dark"] .ai-table-copy-btn.copied {
            background: #10b981;
            border-color: #10b981;
        }

        html[data-bs-theme="dark"] .ai-rich-table,
        html[data-bs-theme="dark"] .ai-bubble table {
            border-color: rgba(148, 163, 184, .25);
        }

        html[data-bs-theme="dark"] .ai-rich-table th,
        html[data-bs-theme="dark"] .ai-rich-table td,
        html[data-bs-theme="dark"] .ai-bubble table th,
        html[data-bs-theme="dark"] .ai-bubble table td {
            border-color: rgba(148, 163, 184, .2);
        }

        /* Force table to not overflow */
        table {
            width: 100% !important;
            max-width: 100% !important;
            overflow-x: auto !important;
            table-layout: fixed;
        }

        table tbody,
        table thead {
            width: 100%;
        }

        table tr {
            width: 100%;
        }

        table td,
        table th {
            word-wrap: break-word;
            word-break: break-word;
        }

        /* ============================================
           DOWNLOADS
           ============================================ */
        .ai-download-root {
            display: flex;
            flex-direction: column;
            gap: var(--ai-md);
        }

        .ai-download-title {
            font-size: 12px;
            font-weight: 700;
            color: var(--ai-muted);
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .ai-download-list {
            display: flex;
            flex-direction: column;
            gap: var(--ai-md);
        }

        .ai-download-item {
            display: flex;
            align-items: center;
            gap: var(--ai-md);
            padding: var(--ai-md);
            border: 1px solid var(--ai-border);
            border-radius: var(--ai-radius-md);
            text-decoration: none;
            color: inherit;
            background: var(--ai-soft);
            transition: all 0.15s ease;
            cursor: pointer;
        }

        .ai-download-item:hover {
            filter: brightness(1.02);
        }

        .ai-download-icon {
            font-size: 18px;
            line-height: 1;
            flex-shrink: 0;
        }

        .ai-download-meta {
            min-width: 0;
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .ai-download-name {
            font-size: 13px;
            font-weight: 600;
            color: var(--ai-text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .ai-download-type {
            font-size: 11px;
            color: var(--ai-muted);
        }

        .ai-download-action {
            font-size: 12px;
            font-weight: 700;
            color: var(--ai-primary);
            white-space: nowrap;
        }

        html[data-bs-theme="dark"] .ai-download-action {
            color: #93c5fd;
        }

        /* ============================================
           JSON DISPLAY
           ============================================ */
        .ai-json-root,
        .ai-json-object {
            display: flex;
            flex-direction: column;
            gap: var(--ai-md);
        }

        .ai-json-section {
            border: 1px solid var(--ai-border);
            border-radius: var(--ai-radius-sm);
            overflow: hidden;
        }

        .ai-json-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
            padding: var(--ai-sm) var(--ai-md);
            background: var(--ai-soft);
            color: var(--ai-muted);
        }

        .ai-json-content {
            padding: var(--ai-md);
        }

        .ai-json-list {
            margin: 0;
            padding-left: 18px;
        }

        .ai-json-list li {
            margin: var(--ai-xs) 0;
        }

        .ai-json-muted {
            color: var(--ai-muted);
            font-style: italic;
        }

        /* ============================================
           COMPOSER & INPUT
           ============================================ */
        .ai-composer {
            padding: var(--ai-md) var(--ai-lg) var(--ai-sm);
            border-top: 1px solid var(--ai-border);
            background: var(--ai-surface);
            flex-shrink: 0 !important;
            flex-grow: 0 !important;
            position: relative;
            z-index: 10;
            width: 100%;
            min-width: 0;
            overflow: visible !important;
        }

        .ai-composer-inner {
            max-width: min(var(--ai-content-max-width), calc(100% - 40px));
            margin: 0 auto;
            display: flex;
            gap: var(--ai-md);
            align-items: flex-end;
            width: 100%;
        }

        .ai-composer textarea {
            border-radius: var(--ai-radius-xl);
            padding: var(--ai-md) var(--ai-lg);
            border: 1px solid var(--ai-border);
            background: var(--ai-soft);
            color: var(--ai-text);
            resize: none;
            min-height: 46px;
            font-family: inherit;
            font-size: 14px;
            transition: all 0.15s ease;
        }

        .ai-composer textarea:focus {
            outline: none;
            border-color: var(--ai-primary);
            background: var(--ai-bg);
        }

        .ai-send {
            width: 46px;
            height: 46px;
            border-radius: 16px;
            border: 1px solid rgba(59, 130, 246, .45);
            background: linear-gradient(135deg, rgba(37, 99, 235, .20), rgba(59, 130, 246, .14));
            color: var(--ai-primary);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s ease;
            flex-shrink: 0;
        }

        .ai-send:hover {
            filter: brightness(1.05);
        }

        .ai-send:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* ============================================
           ANIMATIONS
           ============================================ */
        .ai-stream-hidden {
            display: none;
            white-space: pre-wrap;
        }

        /* AI Thinking Modern UI */
        .ai-thinking-modern {
            margin-top: 5px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .ai-thinking-active {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #2563eb;
            font-size: 14px;
            font-weight: 600;
        }

        .ai-spin-icon {
            font-size: 18px;
        }

        .ai-stream-ctx {
            font-size: 13px;
            color: var(--ai-muted);
            font-weight: 500;
        }

        .ai-thinking-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--ai-text);
        }

        .ai-thinking-sub {
            font-size: 13px;
            color: var(--ai-muted);
            min-height: 20px;
        }

        .ai-thinking-steps {
            display: grid;
            gap: 6px;
            font-size: 12px;
            color: var(--ai-text);
        }

        .ai-thinking-step {
            opacity: 0;
            transform: translateY(4px);
            transition: opacity .22s ease, transform .22s ease;
            padding: 4px 6px;
            border-radius: 8px;
            background: rgba(59, 130, 246, .06);
        }

        .ai-thinking-step.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        .ai-thinking-step.is-fade-out {
            opacity: 0;
            transform: translateY(-3px);
        }

        .ai-dots span {
            display: inline-block;
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background: var(--ai-muted);
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

        @keyframes aiPulseCard {

            0%,
            100% {
                box-shadow: 0 0 0 rgba(59, 130, 246, 0);
            }

            50% {
                box-shadow: 0 4px 14px rgba(59, 130, 246, .08);
            }
        }

        /* ============================================
           REASONING BLOCK
           ============================================ */
        .ai-reasoning-block {
            margin-bottom: var(--ai-sm);
            border-radius: var(--ai-radius-sm);
            border: 1px solid rgba(148, 163, 184, .35);
            background: var(--ai-soft);
            overflow: hidden;
            font-size: 13px;
        }

        .ai-reasoning-summary {
            padding: var(--ai-sm) var(--ai-md);
            cursor: pointer;
            list-style: none;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            color: var(--ai-muted);
            user-select: none;
            transition: background 0.15s ease;
        }

        .ai-reasoning-summary::-webkit-details-marker {
            display: none;
        }

        .ai-reasoning-summary:hover {
            background: rgba(148, 163, 184, .1);
            color: var(--ai-text);
        }

        .ai-reasoning-chevron {
            margin-left: auto;
            transition: transform 0.2s ease;
        }

        .ai-reasoning-block[open] .ai-reasoning-chevron {
            transform: rotate(180deg);
        }

        .ai-reasoning-content {
            padding: var(--ai-sm) var(--ai-md) var(--ai-md);
            border-top: 1px dashed rgba(148, 163, 184, .25);
            color: var(--ai-muted);
            white-space: pre-wrap;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: 11px;
            line-height: 1.5;
            max-height: 300px;
            overflow-y: auto;
            overflow-x: hidden;
            word-wrap: break-word;
            word-break: break-all;
        }

        html[data-bs-theme="dark"] .ai-reasoning-block {
            border-color: rgba(148, 163, 184, .2);
        }

        /* Radical Redesign: AI Capabilities Explorer (2-Column Grid) */
        .no-caret::after {
            display: none !important;
        }

        .ai-capabilities-menu {
            width: min(420px, 92vw);
            min-width: 0;
            max-height: 480px;
            border-radius: 16px;
            overflow-y: auto;
            overflow-x: hidden;
            border: 1px solid var(--ai-border) !important;
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15), 0 4px 12px rgba(0, 0, 0, 0.05);
            padding: 0 !important;
            margin-top: 2px !important;
            transform-origin: top right;
            position: absolute !important;
            top: 100% !important;
            right: 0 !important;
            display: none;
        }

        .ai-capabilities-menu::-webkit-scrollbar {
            width: 4px;
        }

        .ai-capabilities-menu::-webkit-scrollbar-thumb {
            background: var(--ai-border);
            border-radius: 10px;
        }

        .ai-capabilities-menu.show {
            display: block;
            animation: ai-pop-grid 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        html[data-bs-theme="dark"] .ai-capabilities-menu {
            background: rgba(15, 23, 42, 0.85);
            border-color: rgba(255, 255, 255, 0.08) !important;
        }

        @keyframes ai-pop-grid {
            from {
                opacity: 0;
                transform: translateY(0) scale(0.98);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .ai-capabilities-header {
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.08), rgba(99, 102, 241, 0.05));
            padding: 14px 18px;
            border-bottom: 1px solid var(--ai-border);
        }

        .ai-capabilities-heading {
            margin: 0;
            font-weight: 800;
            font-size: 14px;
            color: var(--ai-text);
            letter-spacing: -0.01em;
        }

        .ai-capabilities-sub {
            font-size: 11px;
            color: var(--ai-muted);
            opacity: 0.7;
        }

        .ai-capabilities-list {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            padding: 12px;
        }



        .ai-capabilities-list::-webkit-scrollbar {
            width: 4px;
        }

        .ai-capabilities-list::-webkit-scrollbar-thumb {
            background: var(--ai-border);
            border-radius: 10px;
        }

        .ai-capability-card {
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding: 12px;
            border-radius: 12px;
            background: var(--ai-soft);
            border: 1px solid var(--ai-border);
            transition: all 0.25s ease;
            text-decoration: none;
            color: inherit;
            position: relative;
            overflow: hidden;
        }

        .ai-capability-card:hover {
            background: var(--ai-surface);
            border-color: var(--ai-primary);
            transform: translateY(-3px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.06);
        }

        .ai-capability-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: var(--ai-primary);
            opacity: 0;
            transition: opacity 0.2s;
        }

        .ai-capability-card:hover::before {
            opacity: 0.6;
        }

        .ai-capability-icon-wrap {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            background: white;
            color: var(--ai-primary);
            border: 1px solid var(--ai-border);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.03);
        }

        html[data-bs-theme="dark"] .ai-capability-icon-wrap {
            background: rgba(255, 255, 255, 0.05);
        }

        .ai-capability-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .ai-capability-title {
            font-weight: 700;
            font-size: 12px;
            color: var(--ai-text);
            line-height: 1.2;
        }

        .ai-capability-desc {
            font-size: 10px;
            color: var(--ai-muted);
            line-height: 1.4;
            opacity: 0.8;
        }

        .ai-capabilities-footer {
            padding: 10px 16px;
            border-top: 1px solid var(--ai-border);
            background: rgba(0, 0, 0, 0.03);
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 10px;
            color: var(--ai-muted);
        }

        .ai-capabilities-footer .badge {
            background: var(--ai-primary);
            color: white;
            padding: 3px 6px;
            border-radius: 4px;
            font-weight: 800;
            font-size: 9px;
            box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);
        }

        /* ============================================
           FINAL RESPONSIVE OVERRIDES
           ============================================ */
        @media (min-width: 992px) {
            .ai-app>.ai-sidebar:not(.is-open) {
                width: 0;
                border-right: 0;
                overflow: hidden;
            }

            .ai-app>.ai-sidebar:not(.is-open) .ai-sidebar-inner {
                display: none;
            }

            .ai-overlay {
                display: none !important;
            }

            .ai-empty,
            .ai-composer-inner {
                max-width: min(var(--ai-content-max-width), calc(100% - 40px));
                width: 100%;
                margin-left: auto;
                margin-right: auto;
            }

            .ai-thread {
                width: 100%;
            }

            .ai-msg-bot .ai-bubble {
                max-width: 100%;
            }

            .ai-msg-bot .ai-bubble.ai-bubble-rich {
                width: 100%;
                max-width: min(1280px, 100%);
            }

            .ai-suggest {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        @media (max-width: 991.98px) {
            .ai-sidebar {
                position: absolute;
                top: 0;
                left: 0;
                height: 100%;
                width: var(--ai-sidebar-width);
                transform: translateX(-110%);
                box-shadow: 0 18px 50px rgba(0, 0, 0, .25);
                z-index: 40;
            }

            .ai-sidebar.is-open {
                transform: translateX(0);
            }

            .ai-suggest {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 767.98px) {
            .ai-app {
                height: 100vh !important;
                border-radius: 0;
            }

            .ai-main {
                height: 100vh !important;
            }

            .ai-topbar {
                padding: 10px 12px;
            }

            .ai-body {
                padding: 12px;
            }

            .ai-greeting {
                margin: 20px auto 0;
                padding: 0 16px;
            }

            .ai-greeting.is-collapsed {
                margin: 0 0 15px 0;
                padding: 0;
            }

            .ai-greeting-title {
                font-size: 28px;
            }

            .ai-greeting.is-collapsed .ai-greeting-title {
                font-size: 20px;
            }

            .ai-greeting-sub {
                font-size: 18px;
            }

            .ai-suggest {
                grid-template-columns: 1fr;
                gap: 10px;
                padding: 0;
            }

            .ai-suggest-card {
                padding: 12px;
            }

            .ai-msg-user .ai-bubble,
            .ai-msg-bot .ai-bubble {
                max-width: calc(100% - 20px);
            }

            .ai-msg-user .ai-bubble {
                margin-right: 12px;
            }

            .ai-bubble {
                padding: 12px 14px;
                font-size: 14px;
            }

            .ai-composer {
                padding: 10px 12px;
            }

            .ai-composer-inner {
                max-width: 100%;
                gap: 8px;
            }

            .ai-composer textarea {
                font-size: 16px;
                padding: 10px 12px;
            }

            .ai-send {
                width: 44px;
                height: 44px;
            }

            .ai-rich-table,
            .ai-bubble table {
                font-size: 11px;
            }

            .ai-rich-table th,
            .ai-rich-table td,
            .ai-bubble table th,
            .ai-bubble table td {
                padding: 6px 8px;
                max-width: 150px;
            }
        }

        @media (max-width: 420px) {
            .ai-capabilities-list {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script>
        let __aiProgressObserver = null;
        let __aiProgressCursor = 0;
        let __aiProgressRawLength = 0;
        const __aiRenderedStepKeys = new Set();

        window.__setAiPrompt = (text) => {
            const el = document.getElementById('aiPrompt');
            if (!el) return;
            el.value = text;
            el.dispatchEvent(new Event('input', {
                bubbles: true
            }));
            el.focus();
        };

        const __scrollAiBottom = () => {
            const body = document.getElementById('aiBody');
            if (!body) return;

            // Only auto-scroll if an active chat conversation is present
            // (detected by the greeting being in its collapsed state)
            const isCollapsed = document.querySelector('.ai-greeting.is-collapsed');
            if (!isCollapsed) return;

            requestAnimationFrame(() => {
                body.scrollTop = body.scrollHeight;
            });
        };

        const __setStreamContext = (text, icon = '💡') => {
            const target = document.getElementById('aiStreamContext');
            if (!target || !text) return;
            target.textContent = `${icon} ${text}`;
        };

        const __appendThinkingStep = (type, label) => {
            const wrap = document.getElementById('aiThinkingSteps');
            if (!wrap || !label) return;

            const key = `${type}|${label}`;
            if (__aiRenderedStepKeys.has(key)) return;
            __aiRenderedStepKeys.add(key);

            let prefix = '•';
            if (type === 'tool') prefix = '🛠️';
            if (type === 'phase') prefix = '⏳';
            if (type === 'done') prefix = '✅';

            const line = document.createElement('div');
            line.className = 'ai-thinking-step';
            line.textContent = `${prefix} ${label}`;
            wrap.appendChild(line);

            requestAnimationFrame(() => {
                line.classList.add('is-visible');
            });

            const steps = wrap.querySelectorAll('.ai-thinking-step');
            if (steps.length > 2) {
                const oldest = steps[0];
                oldest.classList.add('is-fade-out');
                setTimeout(() => oldest.remove(), 180);
            }
        };

        const __resetThinkingSteps = () => {
            const wrap = document.getElementById('aiThinkingSteps');
            if (wrap) {
                wrap.innerHTML = '';
                const initial = document.createElement('div');
                initial.className = 'ai-thinking-step is-visible';
                initial.textContent = '🔎 Understanding your question';
                wrap.appendChild(initial);
            }
            __setStreamContext('Preparing analysis and summary in a clean format.', '💡');
            __aiRenderedStepKeys.clear();
            __aiRenderedStepKeys.add('phase|Understanding your question');
            __aiProgressCursor = 0;
            __aiProgressRawLength = 0;
        };

        const __bindAiProgress = () => {
            const source = document.getElementById('aiProgressHidden');

            if (!source) {
                if (__aiProgressObserver) {
                    __aiProgressObserver.disconnect();
                    __aiProgressObserver = null;
                }
                return;
            }

            if (__aiProgressObserver) return;

            const sync = () => {
                const raw = source.innerText || source.textContent || '';

                if (raw.length < __aiProgressRawLength) {
                    __resetThinkingSteps();
                }
                __aiProgressRawLength = raw.length;

                const lines = raw
                    .split(/\r?\n/)
                    .map(line => line.trim())
                    .filter(line => line.length > 0);

                for (let index = __aiProgressCursor; index < lines.length; index++) {
                    const line = lines[index];
                    const parts = line.split('|');
                    const type = (parts[0] || 'phase').trim();
                    const label = parts.slice(1).join('|').trim();

                    if (!label) continue;

                    __appendThinkingStep(type, label);

                    if (type === 'tool') {
                        __setStreamContext(label, '🧩');
                    } else if (type === 'done') {
                        __setStreamContext(label, '✅');
                    } else {
                        __setStreamContext(label, '💡');
                    }
                }

                __aiProgressCursor = lines.length;
            };

            sync();
            __aiProgressObserver = new MutationObserver(sync);
            __aiProgressObserver.observe(source, {
                childList: true,
                characterData: true,
                subtree: true,
            });
        };

        const __updateAnswerPreview = () => {
            const answerHidden = document.getElementById('aiStreamHidden');
            const preview = document.getElementById('aiAnswerPreview');

            if (!answerHidden || !preview) return;

            const fullText = (answerHidden.innerText || answerHidden.textContent || '').trim();
            if (!fullText) return;

            // Extract first sentence (until period, exclamation, or question mark)
            let firstSentence = fullText.split(/[.!?]/)[0] || fullText.substring(0, 150);
            firstSentence = firstSentence.trim();

            // Truncate if too long
            if (firstSentence.length > 150) {
                firstSentence = firstSentence.substring(0, 150) + '...';
            }

            preview.textContent = firstSentence;
        };

        const __htmlTableToMarkdown = (table) => {
            const rows = Array.from(table.querySelectorAll('tr'));
            if (rows.length === 0) return '';

            const matrix = rows.map((row) => {
                return Array.from(row.querySelectorAll('th, td')).map((cell) => {
                    return cell.textContent
                        .replace(/\|/g, '\\|')
                        .replace(/\s+/g, ' ')
                        .trim();
                });
            }).filter((row) => row.length > 0);

            if (matrix.length === 0) return '';

            const maxCols = Math.max(...matrix.map((row) => row.length));
            const normalized = matrix.map((row) => {
                const copy = row.slice();
                while (copy.length < maxCols) copy.push('');
                return copy;
            });

            const widths = Array.from({
                length: maxCols
            }, (_, col) => {
                return normalized.reduce((max, row) => Math.max(max, row[col].length), 0);
            });

            const formatRow = (row) => '| ' + row.map((value, i) => value.padEnd(widths[i], ' ')).join(' | ') + ' |';

            const header = formatRow(normalized[0]);
            const separator = '| ' + widths.map((width) => '-'.repeat(Math.max(3, width))).join(' | ') + ' |';
            const body = normalized.slice(1).map(formatRow);

            return [header, separator, ...body].join('\n');
        };

        const __writeToClipboard = async (text) => {
            if (!text) return false;

            // Preferred path: secure context clipboard API.
            if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
                try {
                    await navigator.clipboard.writeText(text);
                    return true;
                } catch (err) {
                    // Fall through to legacy fallback.
                }
            }

            // Fallback for HTTP / restricted environments.
            try {
                const textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.setAttribute('readonly', '');
                textarea.style.position = 'fixed';
                textarea.style.top = '-9999px';
                textarea.style.left = '-9999px';
                textarea.style.opacity = '0';

                document.body.appendChild(textarea);
                textarea.focus();
                textarea.select();

                const copied = document.execCommand('copy');
                document.body.removeChild(textarea);
                return copied;
            } catch (err) {
                return false;
            }
        };

        const __addTableCopyButtons = () => {
            const richContents = document.querySelectorAll('.ai-rich-content, .ai-bubble');

            richContents.forEach(container => {
                const tables = container.querySelectorAll('table');

                tables.forEach((table) => {
                    // Skip if already wrapped
                    if (table.parentElement.classList.contains('ai-table-wrapper')) return;

                    // Create wrapper
                    const wrapper = document.createElement('div');
                    wrapper.className = 'ai-table-wrapper';

                    // Create copy button
                    const copyBtn = document.createElement('button');
                    copyBtn.className = 'ai-table-copy-btn';
                    copyBtn.innerHTML = '<i class="bx bx-copy"></i> Copy Table';
                    copyBtn.type = 'button';

                    // Add click handler
                    copyBtn.addEventListener('click', async (e) => {
                        e.preventDefault();
                        e.stopPropagation();

                        const markdown = __htmlTableToMarkdown(table);
                        if (!markdown) return;

                        try {
                            const ok = await __writeToClipboard(markdown);
                            if (!ok) throw new Error('Clipboard unavailable');

                            // Visual feedback
                            copyBtn.innerHTML = '<i class="bx bx-check"></i> Copied!';
                            copyBtn.classList.add('copied');

                            setTimeout(() => {
                                copyBtn.innerHTML =
                                    '<i class="bx bx-copy"></i> Copy Table';
                                copyBtn.classList.remove('copied');
                            }, 2000);
                        } catch (err) {
                            console.error('Copy failed:', err);
                        }
                    });

                    // Wrap table
                    table.parentNode.insertBefore(wrapper, table);
                    wrapper.appendChild(copyBtn);
                    wrapper.appendChild(table);
                });
            });
        };

        document.addEventListener('livewire:initialized', () => {
            // More reliable + cheaper than morph.updated hook spam
            Livewire.on('ai-scroll-bottom', () => __scrollAiBottom());

            __resetThinkingSteps();

            setInterval(() => {
                __bindAiProgress();
                __updateAnswerPreview();
                __addTableCopyButtons();
            }, 250);

            // Also scroll after navigation
            document.addEventListener('livewire:navigated', () => {
                __scrollAiBottom();
                __bindAiProgress();
                __addTableCopyButtons();
            });
        });
    </script>

</div>