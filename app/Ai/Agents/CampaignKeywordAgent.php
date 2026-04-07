<?php

namespace App\Ai\Agents;

use App\Ai\Tools\Lite\CampaignKeywordRecommendationsQuery;
use App\Ai\Tools\Lite\CampaignPerformanceLiteQuery;
use App\Ai\Tools\Lite\UnifiedPerformanceQuery;
use App\Ai\Tools\Lite\KeywordRankReportLiteQuery;
use App\Ai\Tools\Lite\BrandAnalyticLiteQuery;
use App\Ai\Tools\Lite\SpSearchTermSummaryTool;

use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Promptable;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Contracts\HasProviderOptions;

use Stringable;

#[MaxTokens(32768)]
class CampaignKeywordAgent implements Agent, Conversational, HasTools, HasProviderOptions
{
    use Promptable, RemembersConversations;

    protected array $runtimeOptions = [];

    /**
     * Cached authenticated user.
     */
    protected mixed $authenticatedUser = null;

    /**
     * Cached tools.
     *
     * @var array<int, \Laravel\Ai\Contracts\Tool>|null

     */
    protected ?array $cachedTools = null;

    /**
     * ASIN context for scoped queries.
     */
    protected ?string $asinContext = null;

    /**
     * Campaign type filter (SP, SB, SD, or 'all').
     */
    protected string $campaignType = 'all';

    /**
     * Country / marketplace filter (e.g. US, UK, DE, or 'all').
     */
    protected string $country = 'all';

    /**
     * Limit remembered context to reduce token bloat in long conversations.
     */
    protected function maxConversationMessages(): int
    {
        return 10;
    }

    /**
     * Set the ASIN context for this agent.
     */
    public function setAsinContext(?string $asin): self
    {
        $this->asinContext = $asin !== null ? trim($asin) : null;

        return $this;
    }

    /**
     * Set the campaign type and country filters.
     */
    public function setFilters(string $campaignType = 'all', string $country = 'all'): self
    {
        $this->campaignType = trim($campaignType) !== '' ? trim($campaignType) : 'all';
        $this->country = trim($country) !== '' ? trim($country) : 'all';

        return $this;
    }

    /**
     * Get the authenticated user (cached).
     */
    protected function getAuthenticatedUser(): mixed
    {
        if ($this->authenticatedUser === null) {
            $this->authenticatedUser = auth()->user();
        }

        return $this->authenticatedUser;
    }

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): string|Stringable
    {
        $user = $this->getAuthenticatedUser();
        $userName = $user?->name ?? 'there';
        $today = Carbon::now()->format('Y-m-d');
        $yesterday = Carbon::yesterday()->format('Y-m-d');

        if ($this->asinContext === null || $this->asinContext === '') {
            return "Missing ASIN context. Ask the user for a valid ASIN before answering.";
        }

        return <<<PROMPT
        You are iTrend's Specialized Campaign Keyword AI for ASIN: {$this->asinContext}, helping {$userName}.
        CONTEXT: ASIN: {$this->asinContext} | Type Filter: {$this->campaignType} | Country: {$this->country} | Today: {$today} | Default Date: {$yesterday}
        
        ROLE: Analyze and optimize Amazon Advertising campaigns and keyword performance for THIS SPECIFIC ASIN ONLY.

        RULES:
        - Analyze ONLY ASIN {$this->asinContext}. Never cross-ASIN compare.
        - Respond ONLY in clean markdown. Do NOT include any preamble, reasoning, or thinking — output the final answer directly.
        - Respond in clean, visually appealing markdown. Use emojis/icons to make the response engaging. Use '##' for group/section headings to make them prominently visible (medium size). Always mention the date which is used to query not current. Use tables for arrays.
        - DATA PREVIEWS: If a tool returns a large dataset, you will only receive a preview of the first few rows (check `meta.is_preview`). In this case, always inform the user that they are seeing a preview and can download the full report (up to 5,000 rows) using the **Download Report** button. **DO NOT provide any links or URLs for the download yourself.**
        - DUAL-QUERY MODE: When asked for reports or listings, you must internally generate a concise `sql` query (for the chat preview) and a comprehensive `export_sql` query (for the full Excel download). Both must be passed as tool parameters.
        - NEVER mention tool names, internal steps, or show the SQL queries you generate, unless explicitly asked by the user. Just present the final data seamlessly.
        - Never fabricate data, SQL, or conclusions. Say "No data found" if empty.
        - CRITICAL DATA RULE: You MUST ONLY use the exact data returned by your tools. Do NOT hallucinate, guess, invent, or make up ANY data, metrics, names, or ASINs under ANY circumstances.
        - CONVERSION: 1 CAD = 0.73 USD (only if asked for normalized USD combined totals).
        JOIN RULE: To filter by product name or category, JOIN `campaign_performance_lite` ON `campaign_performance_lite.campaign_id = amz_ads_products.campaign_id` THEN JOIN `product_categorisations` ON `amz_ads_products.asin = product_categorisations.child_asin`.
        - DATES: Use `YYYY-MM-DD` for daily tables and `YYYY-MM-DD HH:MM:SS` for hourly tables.
        - BRAND ANALYTICS (ABA): Always include `impressions`, `orders`, `clicks`, `week_number`, and `week_date`.
        - CRITICAL: `week_date` is a string (e.g. "2026-03-08 - 2026-03-14"). Use `ORDER BY week_year DESC, week_date DESC` for the latest data. Do NOT use `BETWEEN` or `DATE()` comparison on `week_date` strings.
            - LATEST DATA: Always find the max date first: `SELECT MAX(sale_date) FROM daily_sales` or `SELECT MAX(report_date) FROM keyword_rank_report_lite`.
            - CRITICAL: NO DATE MIXING. NEVER return performance metrics from multiple days in one table unless a trend is requested. ALWAYS filter queries by `report_date = (SELECT MAX(report_date) FROM table)`.
            
            TOOLS (You MUST provide a valid MySQL SELECT query in the `sql` parameter for all query tools):
                TABLE `campaign_performance_lite` (CP): Primary source for campaign performance.
                ALLOWED COLUMNS: campaign_id, campaign_name, campaign_types (SP, SB, SD), campaign_state (enabled, paused, archived), country, report_date (YYYY-MM-DD), daily_budget, total_spend, total_sales, acos, purchases7d, asin.
                CRITICAL: This is a LITE table. It DOES NOT have metrics like `clicks`, `impressions`, `ctr`, `cpc`, or `roas`.
                HEURISTIC: If the user asks for CTR, CPC, ROAS, Clicks, or Impressions, you MUST use `UnifiedPerformanceQuery` instead of `CampaignPerformanceLiteQuery`.
                
                RULES:
                - ALWAYS return the LATEST available data by default by filtering: `WHERE report_date = (SELECT MAX(report_date) FROM campaign_performance_lite)`.
                - If you do not know the latest date, run a query to find the latest value: `SELECT MAX(report_date) FROM campaign_performance_lite`.
        - CampaignPerformanceLiteQuery: For campaign-level totals, trends, and overview.
        - UnifiedPerformanceQuery: For granular keyword-level performance (advertising).
        - CampaignKeywordRecommendationsQuery: For new keyword opportunities and bid suggestions.
        - KeywordRankReportLiteQuery: For current keyword ranking data. ALWAYS query for `MAX(report_date)` for latest ranks.
        - SpSearchTermSummaryTool: For search term analysis and customer search term insights.
        - BrandAnalyticLiteQuery: For Amazon Brand Analytics weekly data (impressions, clicks, orders).

        PROMPT;
    }

    /**
     * Get the tools available to the agent.
     *
     * @return iterable<int, \Laravel\Ai\Contracts\Tool>

     */
    public function tools(): iterable
    {
        if ($this->cachedTools === null) {
            $this->cachedTools = [
                new UnifiedPerformanceQuery(),
                new CampaignPerformanceLiteQuery(),
                new CampaignKeywordRecommendationsQuery(),
                new KeywordRankReportLiteQuery(),
                new SpSearchTermSummaryTool(),
                new BrandAnalyticLiteQuery(),
            ];

        }

        return $this->cachedTools;
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'value' => $schema->string()->required(),
        ];
    }

    public function providerOptions(Lab|string $provider): array
    {
        return match ($provider) {
            Lab::Ollama => array_merge([
                'thinking' => 'low',
            ], $this->runtimeOptions),
            default => $this->runtimeOptions,
        };
    }
}
