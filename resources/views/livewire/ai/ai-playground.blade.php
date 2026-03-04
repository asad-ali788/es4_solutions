<div class="ai-app d-flex" wire:key="ai-shell">

    {{-- Mobile overlay (only when sidebar open) --}}
    <button type="button" class="ai-overlay" wire:click="closeSidebar" aria-label="Close sidebar"
        @class(['d-none' => !$sidebarOpen])>
    </button>

    {{-- Sidebar --}}
    <aside @class(['ai-sidebar', 'is-open' => $sidebarOpen])>
        <div class="ai-sidebar-inner">

            {{-- Brand (robot head toggles sidebar) --}}
            <div class="ai-brand">
                <button type="button" class="ai-brand-icon-btn" wire:click="toggleSidebar" wire:loading.attr="disabled"
                    title="{{ $sidebarOpen ? 'Hide sidebar' : 'Show sidebar' }}">
                    <span class="ai-brand-icon">
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
                            <button type="button" class="ai-chat-item-btn"
                                wire:click="selectConversation('{{ $c['id'] }}')" wire:loading.attr="disabled"
                                title="{{ $c['title'] }}">
                                <div class="ai-chat-item-title">{{ $c['title'] }}</div>
                            </button>

                            <button type="button" class="ai-chat-item-del"
                                wire:click="deleteConversation('{{ $c['id'] }}')" wire:loading.attr="disabled"
                                title="Delete">
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
                            Verify important data before acting.
                        </div>
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
                    @php
                        $selectedCfg = $modelOptions[$selectedModelKey] ?? null;
                        $selectedIsExternal = ($selectedCfg['provider'] ?? 'ollama') !== 'ollama';
                    @endphp
                    <div class="dropdown">
                        <button
                            class="btn btn-sm ai-agent-btn dropdown-toggle {{ $selectedIsExternal ? 'ai-agent-btn-cloud' : '' }}"
                            type="button" data-bs-toggle="dropdown">
                            {{ $selectedModelKey }}
                        </button>

                        <div class="dropdown-menu ai-agent-menu">
                            @foreach ($modelOptions as $label => $cfg)
                                @php $isExternal = (($cfg['provider'] ?? 'ollama') !== 'ollama'); @endphp
                                <button
                                    class="dropdown-item {{ $selectedModelKey === $label ? 'active' : '' }} {{ $isExternal ? 'ai-model-external' : '' }}"
                                    type="button" wire:click="$set('selectedModelKey', '{{ $label }}')">
                                    {{ $label }}
                                    @if ($isExternal)
                                        <span class="ai-model-tag">cloud</span>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="ai-user">
                <div class="ai-user-name text-muted">Local Model</div>
            </div>
        </div>

        @if ($error)
            <div class="ai-error">
                <div class="alert alert-danger mb-0">
                    <i class="bx bx-error-circle me-1"></i>
                    <pre class="mb-0" style="white-space: pre-wrap;">{{ $error }}</pre>
                </div>
            </div>
        @endif

        {{-- Body --}}
        <div class="ai-body" id="aiBody" style="flex: 1; min-height: 0; overflow-y: auto; overflow-x: hidden;">

            {{-- Empty state --}}
            @if (count($messages) === 0 && !$question && !$answer)
                <div class="ai-empty">
                    <div class="ai-empty-title">
                       👋 <span class="ai-gradient"> Hello {{ auth()->user()->name ?? 'there' }}</span>
                    </div>
                    <div class="ai-empty-sub">✨ How can I help you today?</div>

                    <div class="ai-suggest">
                        <button type="button" class="ai-suggest-card"
                            onclick="window.__setAiPrompt('Show top selling products by revenue and units for the last 7 days, including key trends.')">
                            <div class="ai-suggest-icon"><i class="bx bx-bar-chart-alt-2"></i></div>
                            <div class="ai-suggest-title">📊 Top Products</div>
                            <div class="ai-suggest-text">View best-selling SKUs by sales and quantity to spot
                                winning items.</div>
                        </button>

                        <button type="button" class="ai-suggest-card"
                            onclick="window.__setAiPrompt('Analyze Amazon campaign performance (ACOS, ROAS, spend, and sales) and suggest practical optimization actions.')">
                            <div class="ai-suggest-icon"><i class="bx bx-trending-up"></i></div>
                            <div class="ai-suggest-title">🚀 Campaign Performance</div>
                            <div class="ai-suggest-text">Review ACOS, ROAS, spend, and sales with clear
                                optimization actions.</div>
                        </button>

                        <button type="button" class="ai-suggest-card"
                            onclick="window.__setAiPrompt('Show warehouse stock details and highlight low-stock and overstock items.')">
                            <div class="ai-suggest-icon"><i class="bx bx-package"></i></div>
                            <div class="ai-suggest-title">📦 Warehouse Stock</div>
                            <div class="ai-suggest-text">Track current inventory and identify stock risk areas.</div>
                        </button>
                    </div>
                </div>
            @endif

            {{-- Messages --}}
            <div class="ai-thread">
                @foreach ($messages as $m)
                    @if (($m['role'] ?? '') === 'user')
                        <div class="ai-msg ai-msg-user">
                            <div class="ai-bubble">
                                <div style="white-space: pre-wrap;">💬 {{ $m['content'] }}</div>
                            </div>
                        </div>
                    @else
                        <div class="ai-msg ai-msg-bot">
                            <div class="ai-avatar">
                                <svg width="25" height="25" viewBox="0 0 128 128"
                                    xmlns="http://www.w3.org/2000/svg">
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
                                    <circle cx="34" cy="86" r="6" fill="none"
                                        stroke="url(#empty-grad)" stroke-width="8" />
                                </svg>
                            </div>
                            <div class="ai-bubble {{ ($m['is_html'] ?? false) === true ? 'ai-bubble-rich' : '' }}">
                                @if (($m['is_html'] ?? false) === true)
                                    <div class="ai-rich-content">
                                        {!! $m['content'] !!}
                                    </div>
                                @else
                                    <div style="white-space: pre-wrap;">{{ $m['content'] }}</div>
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
                            <div class="ai-avatar"><i class="bx bx-bot"></i></div>
                            <div class="ai-bubble">
                                {{-- target must match the method name that runs --}}
                                <div class="ai-typing" wire:loading wire:target="askStream">
                                    Typing <span class="ai-dots"><span></span><span></span><span></span></span>
                                </div>

                                <div class="ai-thinking-card" wire:loading wire:target="askStream">
                                    <div class="ai-thinking-title" id="aiAnswerPreview">🧠 AI Thinking...</div>
                                    <div class="ai-thinking-sub" id="aiStreamContext">💡 Preparing analysis and summary in a clean format.</div>
                                    <div class="ai-thinking-steps" id="aiThinkingSteps">
                                        <div class="ai-thinking-step is-visible">🔎 Understanding your question</div>
                                    </div>
                                </div>

                                {{-- Hidden stream target keeps backend streaming flow intact without markdown flicker --}}
                                <div id="aiStreamHidden" class="ai-stream-hidden" wire:stream="answer">{{ strip_tags($answer) }}</div>
                                <div id="aiProgressHidden" class="ai-stream-hidden" wire:stream="progress"></div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Composer --}}
        <div class="ai-composer" style="flex: 0 0 auto; padding: 12px 16px; border-top: 1px solid #e5e7eb; background: #f4f6fb; min-width: 0; overflow: visible;">
            <form wire:submit.prevent="submitPrompt" class="ai-composer-inner" style="display: flex; gap: 12px; align-items: flex-end; width: 100%;">
                <textarea id="aiPrompt" class="form-control" wire:model.defer="prompt"
                    wire:keydown.ctrl.enter.prevent="submitPrompt" rows="1" placeholder="Ask something... (Ctrl + Enter to submit)" style="flex: 1; padding: 10px 14px; border-radius: 12px; resize: none; min-height: 44px;"></textarea>

                <button class="btn ai-send" type="submit" wire:loading.attr="disabled" style="width: 44px; height: 44px; flex-shrink: 0;">
                    <span wire:loading.remove wire:target="submitPrompt"><i class="bx bx-send"></i></span>
                    <span wire:loading wire:target="submitPrompt"><i class="bx bx-loader bx-spin"></i></span>
                </button>
            </form>
        </div>
    </main>

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
        @supports (-webkit-touch-callout: none) {
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
        @supports (padding: max(0px)) {
            .ai-composer {
                padding-bottom: max(12px, env(safe-area-inset-bottom));
            }
        }

        .ai-beta-card {
            position: relative;
            padding: 8px 10px 8px 10px;
            border-radius: 10px;
            background: linear-gradient(180deg, rgba(37, 99, 235, .20) 0%, rgba(59, 130, 246, .10) 35%, rgba(255, 255, 255, .94) 100%);
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
            --ai-topbar-height: auto;
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
            background: linear-gradient(180deg, rgba(37, 99, 235, .20) 0%, rgba(59, 130, 246, .10) 35%, rgba(255, 255, 255, .94) 100%);
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
            color: rgba(229, 231, 235, .85);
        }

        /* ============================================
           RESPONSIVE: DESKTOP
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

            /* Consistent sizing for content containers - keep composer and empty constrained */
            .ai-empty,
            .ai-composer-inner {
                max-width: min(var(--ai-content-max-width), calc(100% - 40px));
                width: 100%;
                margin-left: auto;
                margin-right: auto;
            }

            /* Thread expands to full width for bot bubbles */
            .ai-thread {
                width: 100%;
            }

            /* Message bubble sizing */
            .ai-msg-bot .ai-bubble {
                max-width: 100%;
            }

            .ai-msg-bot .ai-bubble.ai-bubble-rich {
                width: 100%;
                max-width: min(1280px, 100%);
            }
        }

        /* ============================================
           RESPONSIVE: MOBILE
           ============================================ */
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
        }

        /* Mobile specific improvements */
        @media (max-width: 767.98px) {
            .ai-app {
                height: 100vh;
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

            .ai-empty {
                margin: 30px auto 0;
            }

            .ai-empty-title {
                font-size: 28px;
            }

            .ai-empty-sub {
                font-size: 18px;
            }

            /* Stack suggestion cards on mobile */
            .ai-suggest {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .ai-suggest-card {
                padding: 12px;
            }

            /* Adjust message bubbles for mobile */
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

            /* Composer adjustments for mobile */
            .ai-composer {
                padding: 10px 12px;
            }

            .ai-composer-inner {
                max-width: 100%;
                gap: 8px;
            }

            .ai-composer textarea {
                font-size: 16px; /* Prevent iOS zoom on focus */
                padding: 10px 12px;
            }

            .ai-send {
                width: 44px;
                height: 44px;
            }

            /* Adjust table sizing for mobile */
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

        /* Tablet specific */
        @media (min-width: 768px) and (max-width: 991.98px) {
            .ai-suggest {
                grid-template-columns: repeat(2, 1fr);
            }
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
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: var(--ai-md) var(--ai-lg);
            border-bottom: 1px solid var(--ai-border);
            background: var(--ai-surface);
            flex-shrink: 0;
            flex-grow: 0;
            min-width: 0;
            max-height: 80px;
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

        .ai-agent-btn.ai-agent-btn-cloud {
            border-color: rgba(59, 130, 246, .45);
            background: linear-gradient(135deg, rgba(59, 130, 246, .14), rgba(99, 102, 241, .12));
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

        .ai-agent-menu .dropdown-item.ai-model-external {
            font-weight: 600;
        }

        .ai-model-tag {
            font-size: 10px;
            line-height: 1;
            padding: var(--ai-xs) var(--ai-sm);
            border-radius: 999px;
            border: 1px solid rgba(59, 130, 246, .35);
            color: #1d4ed8;
            background: rgba(59, 130, 246, .1);
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        html[data-bs-theme="dark"] .ai-model-tag {
            color: #93c5fd;
            border-color: rgba(147, 197, 253, .4);
            background: rgba(59, 130, 246, .18);
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

        .ai-error {
            padding: var(--ai-md) var(--ai-lg) 0;
            flex-shrink: 0;
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

        .ai-empty {
            max-width: 760px;
            margin: 70px auto 0;
            text-align: center;
            width: 100%;
        }

        .ai-empty-title {
            font-size: 42px;
            font-weight: 800;
            margin-bottom: var(--ai-sm);
            color: var(--ai-text);
        }

        .ai-gradient {
            background: linear-gradient(90deg, #1d4ed8 0%, #3b82f6 40%, #6366f1 70%, #8b5cf6 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .ai-empty-sub {
            font-size: 26px;
            color: rgba(107, 114, 128, .75);
            margin-bottom: var(--ai-2xl);
        }

        html[data-bs-theme="dark"] .ai-empty-sub {
            color: rgba(156, 163, 175, .8);
        }

        /* ============================================
           SUGGESTION CARDS
           ============================================ */
        .ai-suggest {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: var(--ai-md);
            margin-top: var(--ai-xl);
        }

        .ai-suggest-card {
            text-align: left;
            border-radius: var(--ai-radius-md);
            border: 1px solid var(--ai-border);
            background: rgba(255, 255, 255, .70);
            padding: var(--ai-md);
            box-shadow: 0 4px 12px rgba(15, 23, 42, .05);
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .ai-suggest-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(15, 23, 42, .08);
        }

        html[data-bs-theme="dark"] .ai-suggest-card {
            background: rgba(15, 23, 42, .75);
        }

        .ai-suggest-icon {
            width: 34px;
            height: 34px;
            border-radius: var(--ai-radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--ai-soft);
            border: 1px solid var(--ai-border);
            margin-bottom: var(--ai-md);
            color: var(--ai-primary);
            flex-shrink: 0;
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
        .ai-typing {
            font-size: 12px;
            color: var(--ai-muted);
            margin-bottom: var(--ai-sm);
        }

        .ai-stream-hidden {
            display: none;
            white-space: pre-wrap;
        }

        .ai-thinking-card {
            border: 1px solid var(--ai-border);
            background: linear-gradient(180deg, rgba(59, 130, 246, .06), rgba(59, 130, 246, .02));
            border-radius: var(--ai-radius-md);
            padding: 12px 14px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            animation: aiPulseCard 1.8s ease-in-out infinite;
            width: min(720px, 100%);
            max-width: 100%;
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

        
    </style>

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

        document.addEventListener('livewire:initialized', () => {
            // More reliable + cheaper than morph.updated hook spam
            Livewire.on('ai-scroll-bottom', () => __scrollAiBottom());

            __resetThinkingSteps();

            setInterval(() => {
                __bindAiProgress();
                __updateAnswerPreview();
            }, 250);

            // Also scroll after navigation
            document.addEventListener('livewire:navigated', () => {
                __scrollAiBottom();
                __bindAiProgress();
            });
        });
    </script>

</div>
