<?php

namespace App\Services\Api;

use OpenAI;
use Illuminate\Support\Facades\Log;
use OpenAI\Exceptions\RateLimitException;


class OpenAIService
{
    protected $client;

    public function __construct()
    {
        $apiKey = config('services.openai.key');

        if (empty($apiKey)) {
            Log::channel('ai')->error('OpenAIService: OPENAI_API_KEY is missing!');
        }

        $this->client = OpenAI::client($apiKey);
    }

    /**
     * Sends a prompt to the AI recommendation engine and returns a structured response.
     *
     * It logs the prompt being sent, communicates with the AI model, and returns
     * the AI’s suggested value and recommendation in JSON-decoded array format.
     *
     * @param  string  $prompt  The constructed instruction set + metrics JSON for AI analysis.
     * @param  string  $type    The recommendation type (e.g., "campaign" or "keywords").
     */
    public function recommendationChat(string $prompt, string $type): ?array
    {
        Log::channel('ai')->info("AI request started", ['prompt' => $prompt]);

        try {
            $systemPrompt = config("ai_prompts.$type");
            sleep(1);
            $result = $this->client->chat()->create([
                'model'    => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemPrompt,
                    ],
                    [
                        'role'    => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens'  => 150,
                'temperature' => 0.5,
                'top_p'       => 1
            ]);

            $rawResponse = $result->choices[0]->message->content ?? null;
            Log::channel('ai')->info("AI raw response", ['response' => $rawResponse]);

            $json = json_decode($rawResponse, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($json)) {
                Log::channel('ai')->warning("AI returned invalid JSON", [
                    'raw'   => $rawResponse,
                    'error' => json_last_error_msg(),
                ]);

                return $this->fallbackResponse("⚠️ Unable to generate recommendation, keep current settings.");
            }

            return [
                'suggested_value' => $this->normalizeBid($json['suggested_value'] ?? null),
                'recommendation' => $json['recommendation'] ?? "⚠️ No recommendation provided.",
            ];
        } catch (\Exception $e) {
            Log::channel('ai')->error('OpenAI recommendationChat failed', [
                'prompt' => $prompt,
                'message' => $e->getMessage(),
            ]);
            sleep(3);
            return $this->fallbackResponse("❌ AI request failed.");
        }
    }


    public function sendBulkRecommendations(array $items, string $promptKey): ?array
    {
        $systemPrompt = config("ai_prompts.$promptKey");
        if (empty($systemPrompt)) {
            Log::channel('ai')->error("AI prompt missing or invalid for key: {$promptKey}");
            return null;
        }

        if (empty($items)) {
            Log::channel('ai')->warning("No items passed for AI bulk recommendation [{$promptKey}]");
            return null;
        }

        // Auto-detect ID field dynamically (e.g., campaign_id, keyword_id)
        // Log::channel('ai')->info('⚠️ Test Payload', [
        //     'item' => $items,
        // ]);
        $first = (array) reset($items);

        $idField = collect(['campaign_id', 'keyword_id', 'id'])
            ->first(fn($field) => array_key_exists($field, $first));

        if (!$idField) {
            Log::channel('ai')->error("No identifier field found in items for prompt [{$promptKey}]");
            return null;
        }

        $maxRetries = 5;
        $retryDelay = 5;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $result = $this->client->chat()->create([
                    'model'       => 'gpt-4o-mini',
                    'messages'    => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => json_encode($items)],
                    ],
                    'max_tokens'  => 3500,
                    'temperature' => 0.5,
                ]);

                $raw = $result->choices[0]->message->content ?? null;

                // Handle invalid responses gracefully
                $decoded = json_decode($raw, true);
                if (!is_array($decoded)) {
                    Log::channel('ai')->warning("Invalid or empty JSON from OpenAI", [
                        'prompt' => $promptKey,
                        'raw'    => json_decode($raw),
                    ]);
                    return null;
                }

                // Normalize result structure
                // OpenAI may return a keyed object or array of objects
                if (array_keys($decoded) === range(0, count($decoded) - 1)) {
                    // JSON is an array of objects — convert to key map
                    $mapped = collect($decoded)->mapWithKeys(function ($item) use ($idField) {
                        $id = $item[$idField] ?? null;
                        if (!$id) return [];
                        return [
                            (string) $id => [
                                'suggested_value' => is_numeric($item['suggested_value'] ?? null)
                                    ? round((float) $item['suggested_value'], 2)
                                    : null,
                                'recommendation'  => $item['recommendation'] ?? null,
                            ]
                        ];
                    })->toArray();
                } else {
                    // JSON is already keyed by ID
                    $mapped = collect($decoded)->mapWithKeys(function ($data, $key) {
                        return [
                            (string) $key => [
                                'suggested_value' => is_numeric($data['suggested_value'] ?? null)
                                    ? round((float) $data['suggested_value'], 2)
                                    : null,
                                'recommendation'  => $data['recommendation'] ?? null,
                            ]
                        ];
                    })->toArray();
                }

                return $mapped;
            } catch (RateLimitException $e) {
                $wait = $retryDelay * $attempt;
                Log::channel('ai')->warning("Rate limit hit. Retrying in {$wait}s (attempt {$attempt}/{$maxRetries})...");
                sleep($wait);
            } catch (\Throwable $e) {
                Log::channel('ai')->error("AI bulk recommendation error", [
                    'error'     => $e->getMessage(),
                    'attempt'   => $attempt,
                    'prompt'    => $promptKey,
                    'exception' => $e,
                ]);
            }
        }

        Log::channel('ai')->error("OpenAI rate limit exceeded after {$maxRetries} retries for prompt [{$promptKey}]");
        return null;
    }


    /**
     * Normalize suggested_value:
     * - number → float with 2 decimals
     * - "not available" → string
     * - null/invalid → null
     */
    private function normalizeBid($value)
    {
        if (is_numeric($value)) {
            return round((float) $value, 2);
        }

        if (is_string($value) && strtolower($value) === 'not available') {
            return 'not available';
        }

        return null;
    }

    /**
     * Default fallback response if AI fails or JSON invalid
     */
    private function fallbackResponse(string $recommendation): array
    {
        return [
            'suggested_value' => null,
            'ai_status'       => 'failed',
            'recommendation'  => $recommendation,
        ];
    }
    
    public function forecastPrediction(array $payload, string $promptKey): ?array
    {
        $systemPrompt = config("ai_forecast_prompts.$promptKey");
        if (empty($systemPrompt) || empty($payload)) return null;

        try {
            $prompt = json_encode([
                "BATCH_INPUT" => $payload
            ], JSON_PRETTY_PRINT);

            Log::channel('ai')->info("AI Forecast Request", ['prompt' => $prompt]);

            $response = $this->client->chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 3000,
                'temperature' => 0.2,
            ]);

            $raw = $response->choices[0]->message->content ?? null;

            Log::channel('ai')->info("AI Forecast Raw Response", ['response' => $raw]);

            $decoded = json_decode($raw, true);

            // Validate new required structure
            if (!is_array($decoded) || !isset($decoded['results'])) {
                Log::channel('ai')->warning("AI Forecast: Invalid JSON structure", ['raw' => $raw]);
                return null;
            }

            return $decoded;
        } catch (\Throwable $e) {
            Log::channel('ai')->error("AI forecast failed", [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }



    public function forecastBulkPrediction(array $asinBatch, string $promptKey): ?array
    {
        $systemPrompt = config("ai_forecast_prompts.$promptKey");
        if (empty($systemPrompt) || empty($asinBatch)) {
            return null;
        }

        try {
            // Build clean user message (batch input)
            $prompt = json_encode([
                'batch' => $asinBatch
            ], JSON_UNESCAPED_SLASHES);

            Log::channel('ai')->info("AI Bulk Forecast Request", ['prompt' => $prompt]);

            $response = $this->client->chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 9000,
                'temperature' => 0.3,
            ]);

            $raw = trim($response->choices[0]->message->content ?? '');
            Log::channel('ai')->info("AI Bulk Forecast Raw Response", ['response' => $raw]);

            if (!$raw) return null;

            // Remove any accidental code fencing
            $raw = preg_replace('/^```(?:json)?/i', '', $raw);
            $raw = preg_replace('/```$/', '', $raw);
            $raw = trim($raw);

            // Remove common AI JSON mistakes
            $raw = preg_replace('/,\s*}/', '}', $raw);   // remove trailing object commas
            $raw = preg_replace('/,\s*]/', ']', $raw);   // remove trailing array commas

            $decoded = json_decode($raw, true);

            if (!is_array($decoded)) {
                Log::channel('ai')->warning("Invalid AI response after decode", [
                    'cleaned_json' => $raw
                ]);
                return null;
            }

            // Normalize snapshot ID keys
            $normalized = [];
            foreach ($decoded as $snapshotId => $monthsData) {
                $normalized[(string)$snapshotId] = is_array($monthsData) ? $monthsData : [];
            }

            // Build the final DB-compatible formatted output
            $final = [];

            foreach ($asinBatch as $item) {

                $snapshotId = (string)$item['snapshot_id'];
                $nextMonths = $item['next_12_months'];

                $aiMonths = $normalized[$snapshotId] ?? [];

                $formatted = [];

                foreach ($nextMonths as $monthKey) {

                    $metrics = $aiMonths[$monthKey] ?? [];

                    $formatted["month_{$monthKey}"] = [
                        'ai_key'        => "month_{$monthKey}",
                        'ai_label'      => "month_{$monthKey}",
                        'ai_sold'       => isset($metrics['ai_sold']) ? (int)$metrics['ai_sold'] : 0,
                        'ai_asp'        => isset($metrics['ai_asp']) ? round((float)$metrics['ai_asp'], 2) : 0.00,
                        'ai_acos'       => isset($metrics['ai_acos']) ? round((float)$metrics['ai_acos'], 2) : 0.00,
                        'ai_tacos'      => isset($metrics['ai_tacos']) ? round((float)$metrics['ai_tacos'], 2) : 0.00,
                        'ai_ad_sales'   => isset($metrics['ai_ad_sales']) ? round((float)$metrics['ai_ad_sales'], 2) : 0.00,
                        'ai_ad_spend'   => isset($metrics['ai_ad_spend']) ? round((float)$metrics['ai_ad_spend'], 2) : 0.00,
                        'recommendations' => isset($metrics['recommendations']) && is_array($metrics['recommendations'])
                            ? array_values($metrics['recommendations'])
                            : [],
                    ];
                }

                $final[$snapshotId] = $formatted;
            }

            return $final;
        } catch (\Throwable $e) {
            Log::channel('ai')->error("Bulk AI forecast failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }
}
