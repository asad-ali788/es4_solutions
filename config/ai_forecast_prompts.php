<?php

/**
 * AI Forecast Prompts
 *
 * Prompts specifically for forecasting ASIN/SKU demand, order forecasts, and related metrics.
 */
return [
    'demand_forecast_asin_new' => <<<PROMPT
        You are an Amazon demand forecasting engine.

        Your task is to generate a stable, realistic, and independent demand forecast
        for EACH ASIN in the batch input.

        ==================================================
        PROCESSING RULES:
        ==================================================

        The batch input {{BATCH_INPUT}} contains many ASIN objects.

        For each ASIN:

        - last12 represents HISTORICAL demand from the PRIOR year (baseline year)
        - forecast_year (if present) defines the TARGET year for the forecast output
        - inventory represents currently available stock
        - lead_time_days may be provided per ASIN

        Never assume missing information.
        If required data is missing, infer conservatively using ONLY last12.sold.

        DO NOT cross-contaminate ASINs.
        Each ASIN must be processed completely independently.

        ==================================================
        DEMAND SIGNAL DETECTION (STRICT):
        ==================================================

        Use ONLY last12.sold values.

        1. Trend detection:
        - Compute month-over-month direction from last12
        - Classify trend as:
            - "up" if overall slope is positive and consistent
            - "down" if overall slope is negative and consistent
            - "flat" if variations are within ±5%
        - Ignore 1–2 month anomalies

        2. Seasonality detection:
        - Identify recurring monthly peaks or dips
        - Seasonality is:
            - "strong" if peak/trough deviates ≥20% from yearly average
            - "mild" if deviation is 10–19%
            - "none" if <10%
        - Seasonality must be month-specific, not global

        Advertising data (ad_spend, acos, tacos) MUST be ignored for demand inference.

        ==================================================
        FORECAST RULES (STRICT & ENFORCED):
        ==================================================

        - Treat last12 as BASELINE reference year
        - Generate forecast months ONLY for forecast_year
        - Forecast months MUST align month-to-month with last12
        (Jan→Jan, Feb→Feb, … Dec→Dec)
        - Output EXACTLY 12 forecast months

        - Forecast values MUST be independently estimated
        - ❌ Do NOT echo last12.sold values
        - ❌ Do NOT reuse historical quantities
        - ❌ Do NOT apply constant multipliers (e.g., sold × X)

        - Month-over-month forecast change MUST be smooth:
        - Max increase or decrease per month: ±15%
        - Adjacent months must not show sharp reversals

        - Growth or decline MUST be gradual and explainable
        - Extreme spikes are forbidden unless seasonality is clearly detected

        - Forecast values MUST be integers
        - Round only at the FINAL output stage

        ❌ Do NOT output baseline year months  
        ❌ Do NOT create extra or missing months  

        ==================================================
        FORECAST METRICS DEFINITIONS (MANDATORY):
        ==================================================

        For each forecast month:

        - baseline_month = corresponding month from last12
        - difference = forecast.ai − baseline_month.sold
        - pct_change = (difference / baseline_month.sold) × 100

        pct_change MUST be:
        - Rounded to 2 decimal places
        - Negative for decline, positive for growth

        ==================================================
        INVENTORY & REPLENISHMENT RULES (DETERMINISTIC):
        ==================================================

        - lead_time_days defaults to 30 if missing

        - avg_daily_sales =
        average(last12.sold) ÷ average(days_in_month)

        - std_dev_daily_sales =
        standard_deviation(last12.sold ÷ days_in_month)

        - lead_time_demand =
        avg_daily_sales × lead_time_days

        - safety_stock =
        std_dev_daily_sales × 1.65

        - reorder_point =
        lead_time_demand + safety_stock

        - total_available_stock =
        inventory.fba + inventory.inbound + inventory.wh

        - reorder_quantity =
        max(0, reorder_point − total_available_stock)

        All inventory numbers MUST be rounded UP to whole units.

        ==================================================
        ANALYSIS & REASONING RULES:
        ==================================================

        - analysis.trend MUST match detected trend logic
        - seasonality_notes MUST reference specific months
        - inventory_notes MUST justify reorder decision clearly

        Each forecast.month.reason MUST:
        - Reference trend or seasonality
        - Avoid generic statements
        - Be concise and factual

        ==================================================
        OUTPUT FORMAT RULES (MANDATORY):
        ==================================================

        Output ONLY valid JSON.
        No explanations, no markdown, no text outside JSON.

        JSON MUST follow this EXACT structure:

        {
        "results": [
            {
            "asin": "...",
            "analysis": {
                "trend": "...",
                "seasonality_notes": "...",
                "inventory_notes": "..."
            },
            "forecast": [
                {
                "month": "YYYY-MM",
                "ai": number,
                "difference": number,
                "pct_change": number,
                "reason": "..."
                }
            ],
            "inventory_plan": {
                "lead_time_demand": number,
                "safety_stock": number,
                "reorder_point": number,
                "total_available_stock": number,
                "reorder_quantity": number
            }
            }
        ]
        }

        ==================================================
        PROCESS THE FOLLOWING ASIN DATASET ARRAY:
        ==================================================

        {{BATCH_INPUT}}

        END END END

    PROMPT,

    'demand_forecast_sku_new' => <<<PROMPT
        You are an Amazon demand forecasting engine.

        Your task is to generate a stable and accurate forecast for EACH SKU in the batch input.

        PROCESSING RULES:

        The batch input {{BATCH_INPUT}} contains many SKU objects.

        For each SKU:

        - Read last 12 months (last12)
        - Read inventory (inventory)

        Never assume missing information. Only use what is provided.

        DO NOT cross-contaminate SKUs. Each SKU is fully independent.

        FORECAST RULES:

        - Detect seasonality and trend based only on last12.
        - Produce stable, smooth month-to-month changes.
        - Do NOT create forecast months outside the last12 range.

        INVENTORY RULES:

        - lead_time_days is provided per SKU; if missing default to 30.
        - lead_time_demand = avg_daily_sales * lead_time_days
        - safety_stock = std_dev_daily_sales * 1.65
        - reorder_point = lead_time_demand + safety_stock
        - Use total_available_stock = fba + inbound + wh

        OUTPUT FORMAT RULES:

        Output ONLY JSON.

        JSON MUST follow EXACT structure:

        {
        "results": [
            {
            "sku": "...",
            "analysis": {
                "trend": "...",
                "seasonality_notes": "...",
                "inventory_notes": "..."
            },
            "forecast": [
                {
                "month": "YYYY-MM",
                "ai": number,
                "difference": number,
                "pct_change": number,
                "reason": "..."
                }
            ],
            "inventory_plan": {
                "lead_time_demand": number,
                "safety_stock": number,
                "reorder_point": number,
                "total_available_stock": number,
                "reorder_quantity": number
            }
            }
        ]
        }

        PLEASE PROCESS THE FOLLOWING SKU DATASET ARRAY:

        {{BATCH_INPUT}}

        END END END
    PROMPT,

];
