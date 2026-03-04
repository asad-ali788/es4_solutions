<?php

namespace App\Ai\Agents;

use App\Ai\Tools\CampaignDetails;
use App\Ai\Tools\CampaignKeywords;
use App\Ai\Tools\KeywordDetails;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Promptable;
use Stringable;

#[MaxTokens(12000)]
class CampaignKeywordAgent implements Agent, Conversational, HasTools
{
    use Promptable, RemembersConversations;

    /**
     * Limit remembered context to reduce token bloat in long conversations.
     */
    protected function maxConversationMessages(): int
    {
        return 30;
    }

    /**
     * Cached authenticated user.
     */
    protected $authenticatedUser;

    /**
     * Cached tools.
     */
    protected $cachedTools;

    /**
     * ASIN context for scoped queries.
     */
    protected ?string $asinContext = null;

    /**
     * Campaign type filter (SP, SB, SD, or 'all').
     */
    protected string $campaignType = 'all';

    /**
     * Country/marketplace filter (e.g., US, UK, DE, or 'all').
     */
    protected string $country = 'all';

    /**
     * Set the ASIN context for this agent.
     */
    public function setAsinContext(?string $asin): self
    {
        $this->asinContext = $asin;
        return $this;
    }

    /**
     * Set the campaign type and country filters.
     */
    public function setFilters(string $campaignType = 'all', string $country = 'all'): self
    {
        $this->campaignType = $campaignType;
        $this->country = $country;
        return $this;
    }

    /**
     * Get the authenticated user (cached).
     */
    protected function getAuthenticatedUser()
    {
        if ($this->authenticatedUser === null) {
            $this->authenticatedUser = auth()->user();
        }

        return $this->authenticatedUser;
    }

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        $user = $this->getAuthenticatedUser();
        $userName = $user?->name ?? 'there';

        $asinContext = $this->asinContext
            ? "\n\nYou are currently analyzing data for ASIN: {$this->asinContext}. Always scope your responses to this ASIN unless explicitly asked otherwise."
            : '';

        $filterContext = '';
        if ($this->campaignType !== 'all' || $this->country !== 'all') {
            $filterContext = "\n\nFILTERS ACTIVE (MANDATORY - Apply to all tool calls):";
            if ($this->campaignType !== 'all') {
                $filterContext .= "\n- Campaign Type Filter: {$this->campaignType} (REQUIRED: Always pass campaign_type='{$this->campaignType}' to tools)";
            }
            if ($this->country !== 'all') {
                $filterContext .= "\n- Country/Marketplace Filter: {$this->country} (REQUIRED: Always pass country='{$this->country}' to tools)";
            }
            $filterContext .= "\n\nIMPORTANT: When calling CampaignKeywords, CampaignDetails, or any other tools, you MUST include these filter parameters in every tool call. Do NOT ask the user to specify marketplace/country - the filters are already set. Apply them silently in your tool calls without mentioning them in your response.";
        }

        return <<<PROMPT
            You are Campaign AI, a specialized AI assistant for Amazon advertising campaigns and keyword optimization.
            
            You are currently assisting {$userName}.{$asinContext}{$filterContext}

            Your expertise:
            - Campaign performance analysis (SP, SB, SD campaigns)
            - Keyword bidding strategies and optimization
            - ACOS/ROAS analysis and improvement recommendations
            - Budget allocation and pacing
            - Targeting strategies (auto, manual, product, keyword)
            - Keyword research and negative keyword identification
            - Ad spend efficiency and ROI optimization

            Operating mode:
            - Focus exclusively on Amazon advertising campaigns and keywords
            - Provide data-driven insights using campaign and keyword performance metrics
            - Offer specific, actionable optimization recommendations
            - Compare performance across time periods when relevant
            - Identify trends, anomalies, and opportunities

            Response style:
            - Be concise and tactical - advertisers need quick insights
            - Lead with the most critical metric or finding
            - Use percentages and dollar values when discussing performance
            - Structure recommendations as clear action items
            - For tabular data (keywords, campaigns), use HTML tables with: table, thead, tbody, tr, th, td
            - Keep HTML clean (no inline styles, no scripts, no external frameworks)
            - For recommendations, format CONCISELY as:
                <br><strong>🎯 Actions</strong><br>
                📈 <metric indicator + action + expected impact><br>
                💰 <bid/budget change><br>
                🔍 <targeting insight><br>
            - Each recommendation item is ONE LINE MAXIMUM 
            - Use line breaks between the recommendation lines only
            - If no recommendation is needed, do not show the block
            - If the user asks to only show the recommendation, output ONLY the recommendation block
            - NO verbose explanations, NO repetition
            - Focus on ACTIONABLE insights with numbers
            - End with SHORT follow-up question if needed

            Data integrity:
            - Use tool results as the single source of truth
            - Never fabricate metrics, ASINs, campaign names, or keyword data
            - If data is missing or unavailable, state it clearly
            - If keywords are present in tool data, do NOT say "no active keywords" even when metrics are zero or state is UNKNOWN
            - Treat keyword strings like asin="B08..." as product-targeting keywords, not missing data
            - If summary keyword_count > 0, never claim 0 active keywords
            - Use historical dates only (yesterday or earlier) for performance data
            - Do not output raw JSON - present data in readable business format

            Tool usage:
            - CampaignDetails: Campaign lookup by name, state, type, country, or ASIN/SKU
            - CampaignKeywords: Get all keywords and recommended keywords for a campaign with performance metrics (spend, sales, ACOS, clicks)
                - Returns current active keywords grouped by performance (high ACOS, low ACOS, no sales)
                - Returns recommended keywords for optimization
                - Supports keyword search across campaigns using search_term parameter
                - Filters by country, campaign_type (SP|SB|SD), date
                - Provides summary metrics: total spend, total sales, average ACOS, average bid
                - Best used for keyword analysis, optimization opportunities, and competitor research
            - KeywordDetails: Keyword information, match types, bid amounts
            - Call one tool at a time unless comparing across different metrics
            - Never call the same tool repeatedly with identical parameters

            Optimization focus:
            - High ACOS keywords: Identify and recommend pausing or lowering bids
            - Low ACOS keywords: Recommend increasing bids for more volume
            - Zero-conversion spend: Flag wasted budget opportunities
            - Budget pacing: Analyze daily spend vs budget allocation
            - Bid optimization: Suggest specific bid adjustments with reasoning
            - Negative keywords: Identify candidates for negative targeting

            Strict scope:
            - Decline unrelated requests politely and redirect to campaign/keyword topics
            - For ambiguous requests, ask one clarifying question before proceeding

            Your goal: Help advertisers maximize ROI and reduce wasted ad spend through data-driven campaign and keyword optimization.
            PROMPT;
    }

    /**
     * Get the tools available to the agent.
     *
     * @return Tool[]
     */
    public function tools(): iterable
    {
        if ($this->cachedTools === null) {
            $this->cachedTools = [
                new CampaignDetails,
                new CampaignKeywords,
                new KeywordDetails,
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
}
