<?php

namespace App\Jobs\Ai;

use App\Services\Api\OpenAIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class GenerateAiRecommendation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $type;
    protected string $modelClass;   // any Eloquent model
    protected int $modelId;
    protected string $prompt;
    protected string $suggestedColumn;

    public function __construct(string $type,string $modelClass, int $modelId, string $prompt, string $suggestedColumn)
    {
        $this->type            = $type;
        $this->modelClass      = $modelClass;
        $this->modelId         = $modelId;
        $this->prompt          = $prompt;
        $this->suggestedColumn = $suggestedColumn;
    }

    public function handle(OpenAIService $openAIService): void
    {
        /** @var Model $model */
        $model = ($this->modelClass)::find($this->modelId);
        if (! $model) {
            return;
        }

        try {
            $model->update(['ai_status' => 'processing']);
            sleep(1);
            $response = $openAIService->recommendationChat($this->prompt, $this->type);

            $model->update([
                $this->suggestedColumn => $response['suggested_value'] ?? $model->bid,
                'ai_recommendation'    => $response['recommendation'],
                'ai_status'            => $response['ai_status'] ?? 'done',
            ]);
        } catch (\Exception $e) {
            Log::channel('ai')->error('OpenAI recommendationChat job failed', [
                'message' => $e->getMessage(),
            ]);
            $model->update(['ai_status' => 'failed']);
        }
    }
}
