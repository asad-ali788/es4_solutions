<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class RetryCommandAfterDelay implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $command;
    public int $delayMinutes;

    public function __construct(string $command, int $delayMinutes = 5)
    {
        $this->command = $command;
        $this->delayMinutes = $delayMinutes;
    }

    public function handle(): void
    {
        sleep($this->delayMinutes * 60);
        Artisan::call($this->command);
        Log::info("🔁 Retried command: {$this->command}");
    }
}
