<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendAuditLogNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $action,
        public string $modelType,
        public int $modelId,
        public ?int $userId = null
    ) {}

    public function handle(): void
    {
        // Log audit notification for monitoring
        Log::info('Audit Log', [
            'action' => $this->action,
            'model_type' => $this->modelType,
            'model_id' => $this->modelId,
            'user_id' => $this->userId,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
