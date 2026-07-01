<?php

namespace App\Jobs;

use App\Models\GoogleSheetsSyncLog;
use App\Models\InventoryMovement;
use App\Models\LeadFollowUp;
use App\Models\Order;
use App\Models\Payment;
use App\Models\VendorOrder;
use App\Support\GoogleSheets\GoogleSheetsClient;
use App\Support\GoogleSheets\GoogleSheetsPayloadMapper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncRecordToGoogleSheetsJob extends QueuedOperation
{
    /**
     * Configuration-driven relations to preload.
     * Prevents N+1 queries.
     *
     * @var array<string, array<int, string>>
     */
    protected array $relations = [
        Order::class => ['customer'],
        Payment::class => ['order'],
        InventoryMovement::class => ['productSku'],
        LeadFollowUp::class => ['assignedTo'],
        VendorOrder::class => ['vendor'],
    ];

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected string $modelClass,
        protected int|string $modelId,
        protected ?int $syncLogId = null,
        protected string $triggeredBy = 'automatic',
        protected ?int $userId = null
    ) {
        parent::__construct();
    }

    /**
     * Execute the job.
     */
    public function handle(
        GoogleSheetsClient $client,
        GoogleSheetsPayloadMapper $mapper
    ): void {
        if (! Config::get('sheets.enabled', false)) {
            return;
        }

        // 1. Resolve and eager-load model
        $query = $this->modelClass::query();

        if (array_key_exists($this->modelClass, $this->relations)) {
            $query->with($this->relations[$this->modelClass]);
        }

        /** @var Model|null $model */
        $model = $query->find($this->modelId);

        if (! $model) {
            $msg = "Model [{$this->modelClass}] with ID [{$this->modelId}] not found.";
            Log::warning('Google Sheets sync ignored: '.$msg);
            $this->markLogFailed(new \Exception($msg));

            return;
        }

        // 2. Perform mapping
        try {
            $rowPayload = $mapper->map($model);
            $sheetName = $mapper->sheet($this->modelClass);
        } catch (Throwable $e) {
            // Mapping or configuration exceptions are permanent
            Log::error("Google Sheets sync failed: Mapping error on [{$this->modelClass}] ID [{$this->modelId}]: ".$e->getMessage());
            $this->markLogFailed($e);
            $this->fail($e);

            return;
        }

        // Retrieve the explicit unique_key from the entity configuration
        $config = Config::get("sheets.entities.{$this->modelClass}");
        $uniqueKeyColumn = $config['unique_key'] ?? null;

        if (empty($uniqueKeyColumn) || ! array_key_exists($uniqueKeyColumn, $rowPayload)) {
            $e = new \InvalidArgumentException("Google Sheets sync failed: unique_key configuration is missing or invalid for model [{$this->modelClass}].");
            Log::error($e->getMessage());
            $this->markLogFailed($e);
            $this->fail($e);

            return;
        }

        $uniqueValue = trim((string) ($rowPayload[$uniqueKeyColumn] ?? ''));

        if ($uniqueValue === '') {
            $e = new \InvalidArgumentException("Google Sheets sync failed: Resolved unique value is empty for model [{$this->modelClass}] using key [{$uniqueKeyColumn}].");
            Log::error($e->getMessage());
            $this->markLogFailed($e);
            $this->fail($e);

            return;
        }

        // 3. Record/update the sync log event
        $log = $this->getOrCreateLog($uniqueKeyColumn, $uniqueValue, $rowPayload);

        $columnKeys = array_keys($rowPayload);
        $sequentialValues = array_values($rowPayload);

        // 4. Sync to Google Sheets
        try {
            $client->syncRow($sheetName, $columnKeys, $sequentialValues, $uniqueKeyColumn, $uniqueValue);

            // Mark log as success
            if ($log) {
                $log->update([
                    'status' => GoogleSheetsSyncLog::STATUS_SUCCESS,
                    'completed_at' => now(),
                    'payload' => null,
                    'error_message' => null,
                ]);
            }
        } catch (Throwable $e) {
            $this->handleSyncException($e, $log, $rowPayload);
        }
    }

    /**
     * Get or create a GoogleSheetsSyncLog record for this event.
     */
    protected function getOrCreateLog(string $uniqueKey, string $uniqueValue, array $rowPayload): ?GoogleSheetsSyncLog
    {
        try {
            $log = null;
            if ($this->syncLogId) {
                $log = GoogleSheetsSyncLog::find($this->syncLogId);
            }

            if (! $log) {
                $log = GoogleSheetsSyncLog::where('model_class', $this->modelClass)
                    ->where('model_id', $this->modelId)
                    ->whereIn('status', [GoogleSheetsSyncLog::STATUS_QUEUED, GoogleSheetsSyncLog::STATUS_PROCESSING])
                    ->latest()
                    ->first();
            }

            if (! $log) {
                $log = new GoogleSheetsSyncLog;
                $log->model_class = $this->modelClass;
                $log->model_id = $this->modelId;
                $log->triggered_by = $this->triggeredBy;
                $log->user_id = $this->userId;
            }

            $payloadSerialized = json_encode($rowPayload);
            $payloadHash = hash('sha256', $payloadSerialized ?: '');

            $log->unique_key = $uniqueKey;
            $log->unique_value = $uniqueValue;
            $log->payload_hash = $payloadHash;
            $log->status = GoogleSheetsSyncLog::STATUS_PROCESSING;
            $log->attempts = ($log->attempts ?? 0) + 1;

            if ($this->job) {
                $log->job_uuid = method_exists($this->job, 'uuid') ? $this->job->uuid() : null;
                $log->connection = method_exists($this->job, 'getConnectionName') ? $this->job->getConnectionName() : null;
                $log->queue = method_exists($this->job, 'getQueue') ? $this->job->getQueue() : null;
            }

            $log->save();
            $this->syncLogId = $log->id;

            return $log;
        } catch (Throwable $e) {
            Log::error('Failed to manage Google Sheets sync log: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Helper to mark the log as failed directly.
     */
    protected function markLogFailed(Throwable $e): void
    {
        try {
            $log = null;
            if ($this->syncLogId) {
                $log = GoogleSheetsSyncLog::find($this->syncLogId);
            }
            if (! $log) {
                $log = GoogleSheetsSyncLog::where('model_class', $this->modelClass)
                    ->where('model_id', $this->modelId)
                    ->whereIn('status', [GoogleSheetsSyncLog::STATUS_QUEUED, GoogleSheetsSyncLog::STATUS_PROCESSING])
                    ->latest()
                    ->first();
            }

            if ($log) {
                $log->update([
                    'status' => GoogleSheetsSyncLog::STATUS_FAILED,
                    'completed_at' => now(),
                    'error_message' => $e->getMessage(),
                ]);
            }
        } catch (Throwable $ex) {
            // Keep fail-safe
        }
    }

    /**
     * Handle client sync exceptions: categorize transient vs permanent errors.
     */
    protected function handleSyncException(Throwable $e, ?GoogleSheetsSyncLog $log, array $rowPayload): void
    {
        $message = $e->getMessage();
        $code = $e->getCode();

        // 1. Transient exceptions: trigger queue retry back-off
        $isTransient = (
            $code === 429 ||
            str_contains($message, 'quota') ||
            str_contains($message, 'timeout') ||
            str_contains($message, 'connection reset') ||
            str_contains($message, 'Curl error 6') ||
            str_contains($message, 'Curl error 7') ||
            $code >= 500
        );

        if ($isTransient) {
            Log::warning("Google Sheets sync transient error on [{$this->modelClass}] ID [{$this->modelId}], retrying: ".$message);
            if ($log) {
                $log->update(['status' => GoogleSheetsSyncLog::STATUS_QUEUED]);
            }
            throw $e; // Rethrow to let queue handle retrying
        }

        // 2. Permanent exceptions: log and fail fast
        Log::error("Google Sheets sync permanent error on [{$this->modelClass}] ID [{$this->modelId}]: ".$message);
        if ($log) {
            $log->update([
                'status' => GoogleSheetsSyncLog::STATUS_FAILED,
                'completed_at' => now(),
                'error_message' => $message,
                'payload' => Config::get('sheets.logging.store_payloads', true) ? $rowPayload : null,
            ]);
        }
        $this->fail($e);
    }

    /**
     * Handle a job failure (called by Laravel when the job fails permanently).
     */
    public function failed(Throwable $exception): void
    {
        $this->markLogFailed($exception);
    }

    /**
     * Unique identifier components for Laravel ShouldBeUnique deduplication.
     */
    protected function dedupeKeyParts(): array
    {
        return [$this->modelClass, $this->modelId];
    }
}
