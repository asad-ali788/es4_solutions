<div class="modal fade ai-cap-modal" id="aiCapabilitiesModal" tabindex="-1" aria-labelledby="aiCapabilitiesModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content ai-cap-content border-0">

            {{-- ══════════ HEADER ══════════ --}}
            <div class="ai-cap-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="ai-cap-logo-wrap">
                        <x-ai.logo width="26" height="26" />
                    </div>
                    <div>
                        <div class="ai-cap-header-label">INTELLIGENCE CENTER</div>
                        <h5 class="ai-cap-header-title mb-0">iTrend AI Assistant</h5>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="ai-cap-beta-pill">
                        <span class="ai-cap-pulse"></span>
                        BETA
                    </div>
                    <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
            </div>

            {{-- ══════════ BODY ══════════ --}}
            <div class="modal-body p-0">
                <div class="d-flex flex-column flex-lg-row" style="height: calc(85vh - 120px); max-height: 680px;">

                    {{-- Sidebar --}}
                    <div class="ai-cap-sidebar">
                        <div class="ai-cap-sidebar-label">NAVIGATION</div>
                        <nav class="nav flex-column nav-pills ai-cap-nav" id="v-pills-tab" role="tablist"
                            aria-orientation="vertical">
                            <button class="nav-link active" id="v-pills-intro-tab" data-bs-toggle="pill"
                                data-bs-target="#v-pills-intro" type="button" role="tab" aria-selected="true">
                                <i class='bx bx-compass'></i>
                                <span>Introduction</span>
                            </button>
                            <button class="nav-link" id="v-pills-tools-tab" data-bs-toggle="pill"
                                data-bs-target="#v-pills-tools" type="button" role="tab" aria-selected="false">
                                <i class='bx bx-wrench'></i>
                                <span>Tools</span>
                                <em class="ai-cap-nav-badge">9</em>
                            </button>
                            <button class="nav-link" id="v-pills-prompts-tab" data-bs-toggle="pill"
                                data-bs-target="#v-pills-prompts" type="button" role="tab" aria-selected="false">
                                <i class='bx bx-chat'></i>
                                <span>Prompts</span>
                            </button>
                            <button class="nav-link" id="v-pills-errors-tab" data-bs-toggle="pill"
                                data-bs-target="#v-pills-errors" type="button" role="tab" aria-selected="false">
                                <i class='bx bx-error-circle'></i>
                                <span>Errors</span>
                            </button>
                        </nav>

                        <!-- <div class="ai-cap-sidebar-footer">
                            <i class='bx bx-ghost me-1'></i> Powered by AI
                        </div> -->
                    </div>

                    {{-- Main content --}}
                    <div class="ai-cap-main flex-grow-1 overflow-auto">
                        <div class="tab-content h-100" id="v-pills-tabContent">

                            {{-- ── INTRODUCTION ── --}}
                            <div class="tab-pane fade show active" id="v-pills-intro" role="tabpanel"
                                aria-labelledby="v-pills-intro-tab">
                                <div class="ai-cap-section">

                                    {{-- Hero banner --}}
                                    <div class="ai-cap-intro-hero mb-4">
                                        <div class="ai-cap-intro-hero-icon">
                                            <i class='bx bx-chip'></i>
                                        </div>
                                        <div>
                                            <h2 class="ai-cap-hero-title">Welcome to <span
                                                    class="ai-cap-gradient-text">iTrend AI</span></h2>
                                            <p class="ai-cap-hero-sub">Your intelligent Amazon analytics co-pilot — ask
                                                questions in plain English and get data-driven answers instantly.</p>
                                        </div>
                                    </div>

                                    {{-- Beta notice --}}
                                    <div class="ai-cap-notice ai-cap-notice--amber mb-4">
                                        <div class="ai-cap-notice-icon">
                                            <i class='bx bx-info-circle'></i>
                                        </div>
                                        <div>
                                            <p class="ai-cap-notice-title">This assistant is in <strong>BETA</strong>
                                            </p>
                                            <p class="ai-cap-notice-body">iTrend AI is highly capable, but artificial
                                                intelligence can still make mistakes or return incomplete data. Always
                                                verify critical decisions before acting on AI-generated results.</p>
                                        </div>
                                    </div>

                                    {{-- Why mistakes happen --}}
                                    <h6 class="ai-cap-section-heading mb-3"><i class='bx bx-help-circle me-2'></i>Why
                                        might the AI make mistakes?</h6>
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-4">
                                            <div class="ai-cap-info-card">
                                                <div class="ai-cap-info-icon"
                                                    style="background:#fef9c3; color:#b45309;">
                                                    <i class='bx bx-ghost'></i>
                                                </div>
                                                <div>
                                                    <div class="ai-cap-info-title">Hallucination</div>
                                                    <div class="ai-cap-info-body">AI occasionally creates confident but
                                                        incorrect answers when data is missing or ambiguous.</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="ai-cap-info-card">
                                                <div class="ai-cap-info-icon"
                                                    style="background:#e0f2fe; color:#0369a1;">
                                                    <i class='bx bx-time-five'></i>
                                                </div>
                                                <div>
                                                    <div class="ai-cap-info-title">Sync Latency</div>
                                                    <div class="ai-cap-info-body">Amazon SP-API data can lag 24–48 hours
                                                        behind Seller Central. Most gaps are due to this delay.</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="ai-cap-info-card">
                                                <div class="ai-cap-info-icon"
                                                    style="background:#fce7f3; color:#be185d;">
                                                    <i class='bx bx-calendar-x'></i>
                                                </div>
                                                <div>
                                                    <div class="ai-cap-info-title">Date Mismatches</div>
                                                    <div class="ai-cap-info-body">Differences between your local time
                                                        and Amazon's server time (PST) can shift metrics by a day.</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Engine --}}
                                    <h6 class="ai-cap-section-heading mb-3"><i class='bx bx-chip me-2'></i>Under the
                                        Hood</h6>
                                    <div class="d-flex flex-wrap gap-2 mb-5">
                                        <div class="ai-cap-model-badge">GPT-OSS 120B Cloud</div>
                                        <div class="ai-cap-model-badge">GPT-OSS 20B Cloud</div>
                                        <div class="ai-cap-model-badge">GPT-5 General Intelligence</div>
                                        <div class="ai-cap-model-badge">Qwen Coder Next</div>
                                    </div>

                                    {{-- Roadmap --}}
                                    <div class="ai-cap-roadmap-strip">
                                        <i class='bx bx-rocket me-2'></i>
                                        <span><strong>Continuously improving:</strong> We train our models daily to
                                            better understand Amazon marketplace nuances — expect smarter tool selection
                                            and faster data retrieval every week.</span>
                                    </div>

                                </div>
                            </div>

                            {{-- ── TOOLS ── --}}
                            <div class="tab-pane fade" id="v-pills-tools" role="tabpanel"
                                aria-labelledby="v-pills-tools-tab">
                                <div class="ai-cap-section">
                                    <div class="d-flex align-items-start justify-content-between mb-4">
                                        <div>
                                            <h4 class="ai-cap-page-title mb-1">Intelligence Toolkit</h4>
                                            <p class="ai-cap-page-sub">Choose the right agent for your analytics
                                                requirements.</p>
                                        </div>
                                        <span class="ai-cap-count-pill">9 Tools</span>
                                    </div>
                                    <div class="row g-3">
                                        @php
                                            $allTools = [
                                                ['icon' => 'bx-trophy', 'title' => 'Top Selling', 'desc' => 'Analyze historical ASIN performance and retrieve ranked sales reports.', 'color' => '#f59e0b', 'cat' => 'Inventory'],
                                                ['icon' => 'bx-search-alt', 'title' => 'Search Term', 'desc' => 'Discover customer query insights via advanced search trend analysis.', 'color' => '#3b82f6', 'cat' => 'Advertising'],
                                                ['icon' => 'bx-trending-up', 'title' => 'Keywords', 'desc' => 'Detailed rank tracking and indexing for individual products.', 'color' => '#10b981', 'cat' => 'Rankings'],
                                                ['icon' => 'bx-error-circle', 'title' => 'Campaigns', 'desc' => 'Spot wasteful spend and get critical campaign health alerts in seconds.', 'color' => '#ef4444', 'cat' => 'Advertising'],
                                                ['icon' => 'bx-package', 'title' => 'Warehouse', 'desc' => 'Check inventory levels and receive automated low-stock warnings.', 'color' => '#8b5cf6', 'cat' => 'Logistics'],
                                                ['icon' => 'bx-key', 'title' => 'Amazon KW', 'desc' => 'Pull direct Amazon keyword recommendations for your live listings.', 'color' => '#0ea5e9', 'cat' => 'Insights'],
                                                ['icon' => 'bx-bar-chart-alt-2', 'title' => 'Bidding', 'desc' => 'Analyze competitive bids and deep-dive advertising performance reports.', 'color' => '#f97316', 'cat' => 'Ads'],
                                                ['icon' => 'bx-time-five', 'title' => 'Hourly Sales', 'desc' => 'Real-time intraday sales performance metrics and trend visualization.', 'color' => '#14b8a6', 'cat' => 'Real-time'],
                                                ['icon' => 'bx-line-chart', 'title' => 'Brand Analysis', 'desc' => 'In-depth brand-level market share and customer basket insights.', 'color' => '#6366f1', 'cat' => 'Insights'],
                                            ];
                                        @endphp
                                        @foreach($allTools as $t)
                                            <div class="col-md-6 col-xl-4">
                                                <div class="ai-cap-tool-card h-100">
                                                    <div class="ai-cap-tool-icon"
                                                        style="background: {{ $t['color'] }}1a; color: {{ $t['color'] }};">
                                                        <i class="bx {{ $t['icon'] }}"></i>
                                                    </div>
                                                    <div class="ai-cap-tool-cat" style="color: {{ $t['color'] }};">
                                                        {{ $t['cat'] }}</div>
                                                    <div class="ai-cap-tool-title">{{ $t['title'] }}</div>
                                                    <div class="ai-cap-tool-desc">{{ $t['desc'] }}</div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            {{-- ── PROMPTS ── --}}
                            <div class="tab-pane fade" id="v-pills-prompts" role="tabpanel"
                                aria-labelledby="v-pills-prompts-tab">
                                <div class="ai-cap-section">
                                    <h4 class="ai-cap-page-title mb-1">Prompting Excellence</h4>
                                    <p class="ai-cap-page-sub mb-4">Precision and context unlock high-quality analytical
                                        results. See the difference below.</p>

                                    {{-- Good vs Bad --}}
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-6">
                                            <div class="ai-cap-prompt-card ai-cap-prompt-card--bad">
                                                <div class="ai-cap-prompt-badge ai-cap-prompt-badge--bad">INEFFICIENT
                                                </div>
                                                <p class="ai-cap-prompt-text">"How are my advertising campaigns doing
                                                    today?"</p>
                                                <div class="ai-cap-prompt-reason ai-cap-prompt-reason--bad">
                                                    <i class='bx bx-x-circle'></i> Lacks target, date range, and
                                                    specific context
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="ai-cap-prompt-card ai-cap-prompt-card--good">
                                                <div class="ai-cap-prompt-badge ai-cap-prompt-badge--good">EXCELLENT
                                                </div>
                                                <p class="ai-cap-prompt-text">"Analyze the ACOS for all Auto Campaigns
                                                    between March 1–15 and highlight anything above 30%."</p>
                                                <div class="ai-cap-prompt-reason ai-cap-prompt-reason--good">
                                                    <i class='bx bx-check-circle'></i> Clear date range, clear target,
                                                    specific criteria
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- More demo prompts --}}
                                    <h6 class="ai-cap-section-heading mb-3"><i class='bx bxs-bulb me-2'></i>Try These
                                        Ready-to-Use Prompts</h6>
                                    <div class="row g-2 mb-4">
                                        @php
                                            $demoPrompts = [
                                                ['icon' => 'bx-bar-chart-alt-2', 'color' => '#6366f1', 'text' => 'Show my top 10 selling ASINs by revenue for the last 30 days.'],
                                                ['icon' => 'bx-trending-up', 'color' => '#10b981', 'text' => 'What is the keyword rank for ASIN B09XXXX for the keyword "wireless earbuds" over the last 60 days?'],
                                                ['icon' => 'bx-time-five', 'color' => '#14b8a6', 'text' => 'Show me hourly sales performance for yesterday from midnight to 6 PM PST.'],
                                                ['icon' => 'bx-package', 'color' => '#8b5cf6', 'text' => 'Which products have inventory below 50 units and are selling more than 5 units per day?'],
                                                ['icon' => 'bx-error-circle', 'color' => '#ef4444', 'text' => 'Find all campaigns where ACoS is above 40% in the last 7 days and spend is over $100.'],
                                                ['icon' => 'bx-search-alt', 'color' => '#3b82f6', 'text' => 'What are the top 5 search terms driving clicks for my brand in the last 30 days?'],
                                                ['icon' => 'bx-line-chart', 'color' => '#f97316', 'text' => 'Compare brand analytics market share for my brand vs competitors in Week -8 vs Week -4.'],
                                                ['icon' => 'bx-trophy', 'color' => '#f59e0b', 'text' => 'Which ASINs have the most reviews in the last 90 days and what is their average rating?'],
                                            ];
                                        @endphp
                                        @foreach($demoPrompts as $dp)
                                            <div class="col-md-6">
                                                <div class="ai-cap-demo-prompt">
                                                    <div class="ai-cap-demo-icon" style="color: {{ $dp['color'] }};">
                                                        <i class="bx {{ $dp['icon'] }}"></i>
                                                    </div>
                                                    <p class="ai-cap-demo-text">"{{ $dp['text'] }}"</p>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    {{-- Pro Tips --}}
                                    <div class="ai-cap-tips-box">
                                        <h6 class="ai-cap-tips-heading"><i class='bx bx-star me-2'></i>Pro Tips</h6>
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <div class="ai-cap-tip-item">
                                                    <div class="ai-cap-tip-icon"><i class='bx bx-target-lock'></i></div>
                                                    <div>
                                                        <div class="ai-cap-tip-title">Names Matter</div>
                                                        <div class="ai-cap-tip-body">Always use precise Campaign names
                                                            or ASINs to avoid broad, inaccurate searches.</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="ai-cap-tip-item">
                                                    <div class="ai-cap-tip-icon"><i class='bx bx-time'></i></div>
                                                    <div>
                                                        <div class="ai-cap-tip-title">Time Context</div>
                                                        <div class="ai-cap-tip-body">Specify "yesterday", "last 30
                                                            days", or exact date ranges for accurate results.</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="ai-cap-tip-item">
                                                    <div class="ai-cap-tip-icon"><i class='bx bx-layer'></i></div>
                                                    <div>
                                                        <div class="ai-cap-tip-title">Chain Questions</div>
                                                        <div class="ai-cap-tip-body">Follow up on previous results:
                                                            <em>"Now, for the first item in that list…"</em></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="ai-cap-tip-item">
                                                    <div class="ai-cap-tip-icon"><i class='bx bx-download'></i></div>
                                                    <div>
                                                        <div class="ai-cap-tip-title">Export to Excel</div>
                                                        <div class="ai-cap-tip-body">Ask "Export this to Excel" after
                                                            any query to download a full-dataset spreadsheet.</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="ai-cap-tip-item">
                                                    <div class="ai-cap-tip-icon"><i class='bx bx-slider-alt'></i></div>
                                                    <div>
                                                        <div class="ai-cap-tip-title">Use Filters</div>
                                                        <div class="ai-cap-tip-body">Narrow results by adding phrases
                                                            like "only for Brand X" or "limit to the top 20".</div>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- ── ERRORS ── --}}
                            <div class="tab-pane fade" id="v-pills-errors" role="tabpanel"
                                aria-labelledby="v-pills-errors-tab">
                                <div class="ai-cap-section">
                                    <h4 class="ai-cap-page-title mb-1">Troubleshooting Guide</h4>
                                    <p class="ai-cap-page-sub mb-4">Facing an issue? Here is why it usually happens and
                                        how to fix it.</p>

                                    <div class="d-flex flex-column gap-3 mb-4">
                                        {{-- Error 1: Unknown column --}}
                                        <div class="ai-cap-error-card ai-cap-error-card--red">
                                            <div class="ai-cap-error-icon"><i class='bx bx-table'></i></div>
                                            <div class="flex-grow-1">
                                                <div class="ai-cap-error-title">Unknown Column in Query</div>
                                                <div class="ai-cap-error-body">The AI generated a SQL query referencing a column that doesn't exist in the table (e.g., <code>Unknown column 'revenue' in field list</code>). This happens when the AI guesses column names instead of using exact schema names.</div>
                                                <div class="ai-cap-error-fix"><i class='bx bx-right-arrow-alt me-1'></i><strong>Fix:</strong> Retry with more specific wording, or tell the AI the exact field — e.g., <em>"use the column ordered_units"</em>.</div>
                                            </div>
                                        </div>

                                        {{-- Error 2: Blank / empty response --}}
                                        <div class="ai-cap-error-card ai-cap-error-card--amber">
                                            <div class="ai-cap-error-icon"><i class='bx bx-ghost'></i></div>
                                            <div class="flex-grow-1">
                                                <div class="ai-cap-error-title">Blank Response from AI</div>
                                                <div class="ai-cap-error-body">Sometimes the AI processes your question but returns nothing — the chat shows a spinning indicator and then stops with no output. This is triggered internally as <em>"AI returned an empty response"</em> and the system automatically retries up to 3 times.</div>
                                                <div class="ai-cap-error-fix"><i class='bx bx-right-arrow-alt me-1'></i><strong>Fix:</strong> Click <strong>Retry</strong> on the failed message, or rephrase and resend your question.</div>
                                            </div>
                                        </div>

                                        {{-- Error 3: Excel broken because export SQL is wrong --}}
                                        <div class="ai-cap-error-card ai-cap-error-card--red">
                                            <div class="ai-cap-error-icon"><i class='bx bx-spreadsheet'></i></div>
                                            <div class="flex-grow-1">
                                                <div class="ai-cap-error-title">Excel Download Fails — Invalid Export SQL</div>
                                                <div class="ai-cap-error-body">The Download Excel button uses a separate, broader SQL query generated by the AI specifically for exports. If that export SQL contains an error (wrong column, wrong table), the download will fail even if the chat preview worked fine.</div>
                                                <div class="ai-cap-error-fix"><i class='bx bx-right-arrow-alt me-1'></i><strong>Fix:</strong> Re-ask your question and explicitly say <em>"export the full dataset to Excel"</em> so the AI regenerates a clean export query.</div>
                                            </div>
                                        </div>

                                        {{-- Error 4: SQL is required --}}
                                        <div class="ai-cap-error-card ai-cap-error-card--amber">
                                            <div class="ai-cap-error-icon"><i class='bx bx-code-block'></i></div>
                                            <div class="flex-grow-1">
                                                <div class="ai-cap-error-title">SQL Is Required — Tool Received No Query</div>
                                                <div class="ai-cap-error-body">Every AI data tool requires a valid SQL SELECT query. If the AI calls a tool without generating one (usually when it misunderstands your request), the system throws <em>"SQL is required"</em> and the tool fails silently. You will typically see no result at all.</div>
                                                <div class="ai-cap-error-fix"><i class='bx bx-right-arrow-alt me-1'></i><strong>Fix:</strong> Rephrase your question to be more data-specific, e.g., <em>"Show me the top 10 ASINs by sales for last month"</em>.</div>
                                            </div>
                                        </div>

                                        {{-- Error 5: Disallowed table reference --}}
                                        <div class="ai-cap-error-card ai-cap-error-card--blue">
                                            <div class="ai-cap-error-icon"><i class='bx bx-lock-alt'></i></div>
                                            <div class="flex-grow-1">
                                                <div class="ai-cap-error-title">Query References a Disallowed Table</div>
                                                <div class="ai-cap-error-body">Each AI tool is restricted to its own set of permitted tables. If the AI generates a query that joins or references a table outside the tool's allowed scope (e.g., querying a campaign table inside a warehouse tool), a security check blocks it with <em>"Query references disallowed table or alias"</em>.</div>
                                                <div class="ai-cap-error-fix"><i class='bx bx-right-arrow-alt me-1'></i><strong>Fix:</strong> Keep your questions scoped to one domain — campaigns, inventory, or keywords — rather than mixing them.</div>
                                            </div>
                                        </div>

                                        {{-- Error 6: AI connection interrupted --}}
                                        <div class="ai-cap-error-card ai-cap-error-card--amber">
                                            <div class="ai-cap-error-icon"><i class='bx bx-wifi-off'></i></div>
                                            <div class="flex-grow-1">
                                                <div class="ai-cap-error-title">AI Connection Interrupted / Timeout</div>
                                                <div class="ai-cap-error-body">The AI model runs on a remote server. If the connection drops mid-stream (network hiccup, server overload, or a model taking too long), the streaming stops. The system automatically retries up to 3 times, then shows the last error as a failure.</div>
                                                <div class="ai-cap-error-fix"><i class='bx bx-right-arrow-alt me-1'></i><strong>Fix:</strong> Click <strong>Retry</strong>. If it keeps failing, try switching to a lighter model (e.g., GPT-OSS 20B) from the model selector.</div>
                                            </div>
                                        </div>

                                        {{-- Error 7: Context too long --}}
                                        <div class="ai-cap-error-card ai-cap-error-card--blue">
                                            <div class="ai-cap-error-icon"><i class='bx bx-history'></i></div>
                                            <div class="flex-grow-1">
                                                <div class="ai-cap-error-title">Conversation Too Long — Context Reset</div>
                                                <div class="ai-cap-error-body">Each AI conversation has a maximum context budget (message count and total characters). When a conversation exceeds this limit, the system automatically clears the thread and starts a fresh one — you may notice a message like <em>"Context too large, starting a fresh thread"</em> in the status bar.</div>
                                                <div class="ai-cap-error-fix"><i class='bx bx-right-arrow-alt me-1'></i><strong>Fix:</strong> This is automatic and expected. Use the <strong>New Chat</strong> button on the left sidebar to start fresh yourself when responses feel slow.</div>
                                            </div>
                                        </div>

                                    </div>

                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            {{-- ══════════ FOOTER ══════════ --}}
            <div class="ai-cap-footer">
                <span class="ai-cap-footer-note"><i class='bx bx-shield-quarter me-1'></i>All queries run in a secure,
                    sandboxed environment.</span>
                <button type="button" class="ai-cap-launch-btn" data-bs-dismiss="modal">
                    <i class='bx bx-rocket me-2'></i>Launch AI Assistant
                </button>
            </div>

        </div>
    </div>
</div>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap');

    /* ─── Root tokens ─────────────────────────────────────── */
    .ai-cap-modal * {
        font-family: 'Outfit', sans-serif;
        box-sizing: border-box;
    }

    /* ─── Dialog sizing ──────────────────────────────────── */
    .ai-cap-modal .modal-dialog {
        height: 85vh;
        max-height: 860px;
    }

    .ai-cap-content {
        height: 100%;
        background: #ffffff;
        border-radius: 22px;
        overflow: hidden;
        box-shadow: 0 40px 80px -20px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(0, 0, 0, 0.06);
        display: flex;
        flex-direction: column;
    }

    /* ─── Header ─────────────────────────────────────────── */
    .ai-cap-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1.1rem 1.75rem;
        background: linear-gradient(135deg, #0c1428 0%, #1a2744 50%, #0f172a 100%);
        color: white;
        flex-shrink: 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        position: relative;
    }

    .ai-cap-header::after {
        content: '';
        position: absolute;
        inset: 0;
        background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Ccircle cx='30' cy='30' r='1' fill='rgba(99,102,241,0.08)'/%3E%3C/svg%3E") repeat;
        pointer-events: none;
    }

    .ai-cap-logo-wrap {
        width: 40px;
        height: 40px;
        background: rgba(99, 102, 241, 0.2);
        border: 1px solid rgba(99, 102, 241, 0.3);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .ai-cap-header-label {
        font-size: 0.65rem;
        font-weight: 700;
        letter-spacing: 0.1em;
        color: rgba(255, 255, 255, 0.45);
        line-height: 1;
        margin-bottom: 2px;
    }

    .ai-cap-header-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: #ffffff;
        line-height: 1.2;
    }

    .ai-cap-beta-pill {
        display: flex;
        align-items: center;
        gap: 7px;
        background: rgba(245, 158, 11, 0.15);
        border: 1px solid rgba(245, 158, 11, 0.35);
        color: #fde68a;
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        padding: 0.35rem 0.85rem;
        border-radius: 999px;
    }

    .ai-cap-pulse {
        width: 7px;
        height: 7px;
        background: #f59e0b;
        border-radius: 50%;
        animation: ai-cap-pulse 2s infinite;
        flex-shrink: 0;
    }

    @keyframes ai-cap-pulse {
        0% {
            box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.7);
        }

        70% {
            box-shadow: 0 0 0 7px rgba(245, 158, 11, 0);
        }

        100% {
            box-shadow: 0 0 0 0 rgba(245, 158, 11, 0);
        }
    }

    /* ─── Sidebar ─────────────────────────────────────────── */
    .ai-cap-sidebar {
        width: 200px;
        min-width: 200px;
        background: #f8fafc;
        border-right: 1px solid #e8ecf0;
        display: flex;
        flex-direction: column;
        padding: 1.25rem 0.85rem;
        flex-shrink: 0;
    }

    .ai-cap-sidebar-label {
        font-size: 0.62rem;
        font-weight: 700;
        letter-spacing: 0.1em;
        color: #94a3b8;
        padding: 0 0.5rem;
        margin-bottom: 0.75rem;
    }

    .ai-cap-nav {
        gap: 3px;
    }

    .ai-cap-nav .nav-link {
        display: flex;
        align-items: center;
        gap: 9px;
        color: #475569;
        font-weight: 600;
        font-size: 0.88rem;
        padding: 0.65rem 0.9rem;
        border-radius: 10px;
        border: 1px solid transparent;
        transition: all 0.18s ease;
        position: relative;
        white-space: nowrap;
    }

    .ai-cap-nav .nav-link i {
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    .ai-cap-nav .nav-link:hover {
        background: rgba(37, 99, 235, 0.06);
        color: #2563eb;
    }

    .ai-cap-nav .nav-link.active {
        background: #ffffff;
        color: #2563eb;
        border-color: rgba(37, 99, 235, 0.12);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        font-weight: 700;
    }

    .ai-cap-nav-badge {
        margin-left: auto;
        background: #2563eb;
        color: #fff;
        font-size: 0.65rem;
        font-weight: 700;
        padding: 2px 7px;
        border-radius: 999px;
        font-style: normal;
    }

    .ai-cap-sidebar-footer {
        margin-top: auto;
        padding: 0.75rem 0.75rem 0.25rem;
        font-size: 0.72rem;
        color: #94a3b8;
        font-weight: 600;
        display: flex;
        align-items: center;
    }

    /* ─── Main content area ───────────────────────────────── */
    .ai-cap-main {
        background: #ffffff;
    }

    .ai-cap-section {
        padding: 2rem 2.25rem;
    }

    /* ─── Typography helpers ──────────────────────────────── */
    .ai-cap-page-title {
        font-size: 1.35rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.2;
    }

    .ai-cap-page-sub {
        font-size: 0.9rem;
        color: #64748b;
        margin-bottom: 0;
    }

    .ai-cap-section-heading {
        font-size: 0.88rem;
        font-weight: 700;
        color: #334155;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        display: flex;
        align-items: center;
    }

    /* ─── Intro hero ─────────────────────────────────────── */
    .ai-cap-intro-hero {
        display: flex;
        align-items: flex-start;
        gap: 1.25rem;
        padding: 1.5rem;
        background: linear-gradient(135deg, #f0f4ff, #fafbff);
        border-radius: 16px;
        border: 1px solid #e0e7ff;
    }

    .ai-cap-intro-hero-icon {
        width: 52px;
        height: 52px;
        background: linear-gradient(135deg, #2563eb, #6366f1);
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.6rem;
        flex-shrink: 0;
        box-shadow: 0 6px 20px rgba(99, 102, 241, 0.35);
    }

    .ai-cap-hero-title {
        font-size: 1.5rem;
        font-weight: 800;
        color: #0f172a;
        margin: 0 0 6px;
        line-height: 1.2;
    }

    .ai-cap-gradient-text {
        background: linear-gradient(135deg, #2563eb, #7c3aed);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .ai-cap-hero-sub {
        font-size: 0.9rem;
        color: #475569;
        margin: 0;
        line-height: 1.55;
    }

    /* ─── Notice / Alert strip ────────────────────────────── */
    .ai-cap-notice {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        padding: 1rem 1.25rem;
        border-radius: 12px;
        border: 1px solid;
    }

    .ai-cap-notice--amber {
        background: #fffbeb;
        border-color: #fde68a;
    }

    .ai-cap-notice-icon {
        width: 34px;
        height: 34px;
        background: #fef3c7;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #b45309;
        font-size: 1.15rem;
        flex-shrink: 0;
    }

    .ai-cap-notice-title {
        font-size: 0.9rem;
        font-weight: 700;
        color: #92400e;
        margin: 0 0 3px;
    }

    .ai-cap-notice-body {
        font-size: 0.83rem;
        color: #78350f;
        margin: 0;
        line-height: 1.5;
    }

    /* ─── Info cards ──────────────────────────────────────── */
    .ai-cap-info-card {
        display: flex;
        align-items: flex-start;
        gap: 0.85rem;
        padding: 1rem;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        height: 100%;
    }

    .ai-cap-info-icon {
        width: 36px;
        height: 36px;
        border-radius: 9px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        flex-shrink: 0;
    }

    .ai-cap-info-title {
        font-size: 0.86rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 3px;
    }

    .ai-cap-info-body {
        font-size: 0.8rem;
        color: #64748b;
        line-height: 1.5;
    }

    /* ─── Model badges ────────────────────────────────────── */
    .ai-cap-model-badge {
        display: inline-flex;
        align-items: center;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        color: #1d4ed8;
        font-size: 0.78rem;
        font-weight: 700;
        padding: 0.35rem 0.9rem;
        border-radius: 8px;
        cursor: default;
    }

    /* ─── Roadmap strip ───────────────────────────────────── */
    .ai-cap-roadmap-strip {
        display: flex;
        align-items: flex-start;
        gap: 0;
        padding: 0.9rem 1.1rem;
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-radius: 10px;
        font-size: 0.83rem;
        color: #166534;
        line-height: 1.5;
    }

    /* ─── Count pill ──────────────────────────────────────── */
    .ai-cap-count-pill {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        color: #1d4ed8;
        font-size: 0.75rem;
        font-weight: 700;
        padding: 0.3rem 0.85rem;
        border-radius: 999px;
        white-space: nowrap;
        flex-shrink: 0;
    }

    /* ─── Tool cards ──────────────────────────────────────── */
    .ai-cap-tool-card {
        padding: 1.1rem;
        border: 1px solid #e8ecf0;
        border-radius: 14px;
        transition: border-color 0.2s, box-shadow 0.2s, transform 0.2s;
        background: #fff;
    }

    .ai-cap-tool-card:hover {
        border-color: #c7d2fe;
        box-shadow: 0 4px 16px rgba(99, 102, 241, 0.1);
        transform: translateY(-2px);
    }

    .ai-cap-tool-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        margin-bottom: 0.65rem;
    }

    .ai-cap-tool-cat {
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.07em;
        text-transform: uppercase;
        margin-bottom: 4px;
        opacity: 0.85;
    }

    .ai-cap-tool-title {
        font-size: 0.95rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 5px;
    }

    .ai-cap-tool-desc {
        font-size: 0.8rem;
        color: #64748b;
        line-height: 1.5;
    }

    /* ─── Prompt cards ────────────────────────────────────── */
    .ai-cap-prompt-card {
        padding: 1.15rem 1.25rem;
        border-radius: 14px;
        border: 1px solid;
        height: 100%;
    }

    .ai-cap-prompt-card--bad {
        background: #fff5f5;
        border-color: #fecaca;
    }

    .ai-cap-prompt-card--good {
        background: #f0fdf4;
        border-color: #bbf7d0;
    }

    .ai-cap-prompt-badge {
        display: inline-block;
        font-size: 0.65rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        padding: 3px 10px;
        border-radius: 999px;
        margin-bottom: 10px;
    }

    .ai-cap-prompt-badge--bad {
        background: #fee2e2;
        color: #b91c1c;
    }

    .ai-cap-prompt-badge--good {
        background: #dcfce7;
        color: #15803d;
    }

    .ai-cap-prompt-text {
        font-size: 0.88rem;
        color: #334155;
        font-style: italic;
        line-height: 1.5;
        margin-bottom: 10px;
    }

    .ai-cap-prompt-reason {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 0.79rem;
        font-weight: 600;
    }

    .ai-cap-prompt-reason--bad {
        color: #dc2626;
    }

    .ai-cap-prompt-reason--good {
        color: #16a34a;
    }

    /* ─── Demo prompts ────────────────────────────────────── */
    .ai-cap-demo-prompt {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 0.85rem 1rem;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        transition: border-color 0.18s, background 0.18s;
        cursor: default;
    }

    .ai-cap-demo-prompt:hover {
        background: #f0f4ff;
        border-color: #c7d2fe;
    }

    .ai-cap-demo-icon {
        font-size: 1.05rem;
        flex-shrink: 0;
        margin-top: 1px;
    }

    .ai-cap-demo-text {
        font-size: 0.8rem;
        color: #334155;
        font-style: italic;
        line-height: 1.5;
        margin: 0;
    }

    /* ─── Pro Tips box ────────────────────────────────────── */
    .ai-cap-tips-box {
        background: #1e293b;
        border-radius: 16px;
        padding: 1.5rem;
    }

    .ai-cap-tips-heading {
        font-size: 0.85rem;
        font-weight: 700;
        color: #ffffff;
        text-transform: uppercase;
        letter-spacing: 0.07em;
        margin-bottom: 1.1rem;
        display: flex;
        align-items: center;
    }

    .ai-cap-tip-item {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 10px;
        padding: 0.85rem;
        height: 100%;
    }

    .ai-cap-tip-icon {
        width: 30px;
        height: 30px;
        background: rgba(99, 102, 241, 0.25);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #a5b4fc;
        font-size: 1rem;
        flex-shrink: 0;
    }

    .ai-cap-tip-title {
        font-size: 0.82rem;
        font-weight: 700;
        color: #e2e8f0;
        margin-bottom: 3px;
    }

    .ai-cap-tip-body {
        font-size: 0.76rem;
        color: #94a3b8;
        line-height: 1.45;
    }

    .ai-cap-tip-body code {
        background: rgba(255, 255, 255, 0.1);
        color: #a5b4fc;
        padding: 1px 5px;
        border-radius: 4px;
        font-size: 0.73rem;
    }

    /* ─── Error cards ─────────────────────────────────────── */
    .ai-cap-error-card {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        padding: 1.1rem 1.25rem;
        border-radius: 14px;
        border: 1px solid;
    }

    .ai-cap-error-card--red {
        background: #fff5f5;
        border-color: #fecaca;
    }

    .ai-cap-error-card--amber {
        background: #fffbeb;
        border-color: #fde68a;
    }

    .ai-cap-error-card--blue {
        background: #eff6ff;
        border-color: #bfdbfe;
    }

    .ai-cap-error-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        flex-shrink: 0;
    }

    .ai-cap-error-card--red .ai-cap-error-icon {
        background: #fee2e2;
        color: #dc2626;
    }

    .ai-cap-error-card--amber .ai-cap-error-icon {
        background: #fef3c7;
        color: #b45309;
    }

    .ai-cap-error-card--blue .ai-cap-error-icon {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .ai-cap-error-title {
        font-size: 0.9rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 4px;
    }

    .ai-cap-error-body {
        font-size: 0.82rem;
        color: #475569;
        line-height: 1.5;
        margin-bottom: 6px;
    }

    .ai-cap-error-fix {
        font-size: 0.8rem;
        color: #334155;
        font-weight: 600;
        display: flex;
        align-items: center;
    }

    .ai-cap-error-fix em {
        font-style: italic;
    }

    /* ─── Reset strip ─────────────────────────────────────── */
    .ai-cap-reset-strip {
        display: flex;
        align-items: flex-start;
        padding: 1.1rem 1.25rem;
        background: #fff5f5;
        border: 1px solid #fecaca;
        border-radius: 14px;
        color: #7f1d1d;
    }

    .ai-cap-reset-body {
        font-size: 0.82rem;
        color: #7f1d1d;
        line-height: 1.5;
    }

    .ai-cap-reset-body code {
        background: #fee2e2;
        color: #dc2626;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 0.8rem;
    }

    /* ─── Footer ─────────────────────────────────────────── */
    .ai-cap-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.85rem 1.75rem;
        background: #f8fafc;
        border-top: 1px solid #e8ecf0;
        flex-shrink: 0;
    }

    .ai-cap-footer-note {
        font-size: 0.78rem;
        color: #94a3b8;
        font-weight: 600;
        display: flex;
        align-items: center;
    }

    .ai-cap-launch-btn {
        display: inline-flex;
        align-items: center;
        background: linear-gradient(135deg, #2563eb, #6366f1);
        color: #fff;
        border: none;
        padding: 0.55rem 1.5rem;
        border-radius: 999px;
        font-size: 0.9rem;
        font-weight: 700;
        cursor: pointer;
        transition: opacity 0.18s, transform 0.18s;
        font-family: 'Outfit', sans-serif;
        letter-spacing: 0.02em;
    }

    .ai-cap-launch-btn:hover {
        opacity: 0.9;
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(37, 99, 235, 0.35);
    }

    /* ─── Dark mode support — comprehensive ───────────────── */

    /* Layout shells */
    html[data-bs-theme="dark"] .ai-cap-content      { background: #0f172a; }
    html[data-bs-theme="dark"] .ai-cap-main         { background: #0f172a; }

    /* Sidebar */
    html[data-bs-theme="dark"] .ai-cap-sidebar      { background: #0c1428; border-color: rgba(255,255,255,0.07); }
    html[data-bs-theme="dark"] .ai-cap-sidebar-label { color: #475569; }
    html[data-bs-theme="dark"] .ai-cap-nav .nav-link { color: #94a3b8; }
    html[data-bs-theme="dark"] .ai-cap-nav .nav-link:hover { background: rgba(148,163,184,0.08); color: #cbd5e1; }
    html[data-bs-theme="dark"] .ai-cap-nav .nav-link.active { background: #1e293b; color: #93c5fd; box-shadow: none; border-color: rgba(147,197,253,0.15); }

    /* Footer */
    html[data-bs-theme="dark"] .ai-cap-footer       { background: #0c1428; border-color: rgba(255,255,255,0.07); }
    html[data-bs-theme="dark"] .ai-cap-footer-note  { color: #475569; }

    /* Typography */
    html[data-bs-theme="dark"] .ai-cap-page-title   { color: #f1f5f9; }
    html[data-bs-theme="dark"] .ai-cap-page-sub     { color: #64748b; }
    html[data-bs-theme="dark"] .ai-cap-section-heading { color: #94a3b8; }

    /* Intro hero */
    html[data-bs-theme="dark"] .ai-cap-intro-hero   { background: linear-gradient(135deg, #1a2235, #0f172a); border-color: rgba(99,102,241,0.15); }
    html[data-bs-theme="dark"] .ai-cap-hero-title   { color: #f1f5f9; }
    html[data-bs-theme="dark"] .ai-cap-hero-sub     { color: #94a3b8; }

    /* Beta notice */
    html[data-bs-theme="dark"] .ai-cap-notice--amber { background: #1c1506; border-color: rgba(245,158,11,0.2); }
    html[data-bs-theme="dark"] .ai-cap-notice-icon  { background: rgba(245,158,11,0.15); }
    html[data-bs-theme="dark"] .ai-cap-notice-title { color: #fde68a; }
    html[data-bs-theme="dark"] .ai-cap-notice-body  { color: #d4a820; }

    /* Info cards (Why mistakes happen) */
    html[data-bs-theme="dark"] .ai-cap-info-card    { background: #1e293b; border-color: rgba(255,255,255,0.07); }
    html[data-bs-theme="dark"] .ai-cap-info-title   { color: #e2e8f0; }
    html[data-bs-theme="dark"] .ai-cap-info-body    { color: #64748b; }

    /* Model badges */
    html[data-bs-theme="dark"] .ai-cap-model-badge  { background: rgba(37,99,235,0.15); border-color: rgba(37,99,235,0.25); color: #93c5fd; }

    /* Roadmap strip */
    html[data-bs-theme="dark"] .ai-cap-roadmap-strip { background: #0d1f12; border-color: rgba(34,197,94,0.2); color: #86efac; }

    /* Tool cards */
    html[data-bs-theme="dark"] .ai-cap-tool-card    { background: #1e293b; border-color: rgba(255,255,255,0.07); }
    html[data-bs-theme="dark"] .ai-cap-tool-card:hover { border-color: rgba(99,102,241,0.3); box-shadow: 0 4px 16px rgba(99,102,241,0.1); }
    html[data-bs-theme="dark"] .ai-cap-tool-title   { color: #f1f5f9; }
    html[data-bs-theme="dark"] .ai-cap-tool-desc    { color: #64748b; }

    /* Count pill */
    html[data-bs-theme="dark"] .ai-cap-count-pill   { background: rgba(37,99,235,0.15); border-color: rgba(37,99,235,0.25); color: #93c5fd; }

    /* Prompt cards — bad */
    html[data-bs-theme="dark"] .ai-cap-prompt-card--bad  { background: #1a0a0a; border-color: rgba(239,68,68,0.2); }
    html[data-bs-theme="dark"] .ai-cap-prompt-card--bad .ai-cap-prompt-text  { color: #fca5a5; }
    html[data-bs-theme="dark"] .ai-cap-prompt-badge--bad  { background: rgba(239,68,68,0.15); color: #fca5a5; }

    /* Prompt cards — good */
    html[data-bs-theme="dark"] .ai-cap-prompt-card--good { background: #0a1a0e; border-color: rgba(34,197,94,0.2); }
    html[data-bs-theme="dark"] .ai-cap-prompt-card--good .ai-cap-prompt-text  { color: #86efac; }
    html[data-bs-theme="dark"] .ai-cap-prompt-badge--good { background: rgba(34,197,94,0.15); color: #86efac; }

    /* Demo prompts */
    html[data-bs-theme="dark"] .ai-cap-demo-prompt        { background: #1e293b; border-color: rgba(255,255,255,0.07); }
    html[data-bs-theme="dark"] .ai-cap-demo-prompt:hover  { background: #263448; border-color: rgba(99,102,241,0.2); }
    html[data-bs-theme="dark"] .ai-cap-demo-text          { color: #94a3b8; }

    /* Pro Tips box — already dark slate, needs subtle adjustment */
    html[data-bs-theme="dark"] .ai-cap-tips-box           { background: #0f172a; border: 1px solid rgba(255,255,255,0.06); }
    html[data-bs-theme="dark"] .ai-cap-tip-item           { background: rgba(255,255,255,0.03); border-color: rgba(255,255,255,0.06); }

    /* Error cards */
    html[data-bs-theme="dark"] .ai-cap-error-card--red    { background: #1a0a0a; border-color: rgba(239,68,68,0.2); }
    html[data-bs-theme="dark"] .ai-cap-error-card--amber  { background: #1c1506; border-color: rgba(245,158,11,0.2); }
    html[data-bs-theme="dark"] .ai-cap-error-card--blue   { background: #07111f; border-color: rgba(59,130,246,0.2); }

    html[data-bs-theme="dark"] .ai-cap-error-card--red   .ai-cap-error-icon { background: rgba(239,68,68,0.15);  color: #fca5a5; }
    html[data-bs-theme="dark"] .ai-cap-error-card--amber .ai-cap-error-icon { background: rgba(245,158,11,0.15); color: #fde68a; }
    html[data-bs-theme="dark"] .ai-cap-error-card--blue  .ai-cap-error-icon { background: rgba(59,130,246,0.15); color: #93c5fd; }

    html[data-bs-theme="dark"] .ai-cap-error-title        { color: #e2e8f0; }
    html[data-bs-theme="dark"] .ai-cap-error-body         { color: #64748b; }
    html[data-bs-theme="dark"] .ai-cap-error-body code    { background: rgba(255,255,255,0.08); color: #a5b4fc; }
    html[data-bs-theme="dark"] .ai-cap-error-body em      { color: #94a3b8; }
    html[data-bs-theme="dark"] .ai-cap-error-fix          { color: #94a3b8; }
    html[data-bs-theme="dark"] .ai-cap-error-fix strong   { color: #cbd5e1; }

    /* ─── Responsive ──────────────────────────────────────── */
    @media (max-width: 991.98px) {
        .ai-cap-sidebar {
            width: 100%;
            min-width: 100%;
            border-right: none;
            border-bottom: 1px solid #e8ecf0;
        }

        .ai-cap-nav {
            flex-direction: row;
            flex-wrap: wrap;
        }
    }

    @media (max-width: 575.98px) {
        .ai-cap-section {
            padding: 1.25rem 1rem;
        }

        .ai-cap-footer {
            flex-direction: column;
            gap: 0.75rem;
            text-align: center;
        }
    }
</style>