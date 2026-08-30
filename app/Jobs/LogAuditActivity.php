<?php
// app/Jobs/LogAuditActivity.php

namespace App\Jobs;

use App\Models\AuditLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LogAuditActivity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected ?int $userId,
        protected ?int $companyId,
        protected string $action,
        protected string $modelType,
        protected int $modelId,
        protected ?array $oldValues,
        protected ?array $newValues,
        protected ?string $ipAddress,
        protected ?string $userAgent,
        protected ?string $url,
        protected ?string $method,
    ) {}

    public function handle(): void
    {
        AuditLog::create([
            'user_id' => $this->userId,
            'company_id' => $this->companyId,
            'action' => $this->action,
            'model_type' => $this->modelType,
            'model_id' => $this->modelId,
            'old_values' => $this->oldValues,
            'new_values' => $this->newValues,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'url' => $this->url,
            'method' => $this->method,
        ]);
    }
}