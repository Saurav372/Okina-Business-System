<?php

namespace App\Jobs;

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
        protected int|string $modelId
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
            Log::warning("Google Sheets sync ignored: Model [{$this->modelClass}] with ID [{$this->modelId}] not found.");

            return;
        }

        // 2. Perform mapping
        try {
            $rowPayload = $mapper->map($model);
            $sheetName = $mapper->sheet($this->modelClass);
            $headers = $mapper->headers($this->modelClass);
        } catch (Throwable $e) {
            // Mapping or configuration exceptions are permanent
            Log::error("Google Sheets sync failed: Mapping error on [{$this->modelClass}] ID [{$this->modelId}]: ".$e->getMessage());
            $this->fail($e);

            return;
        }

        // Retrieve the explicit unique_key from the entity configuration
        $config = Config::get("sheets.entities.{$this->modelClass}");
        $uniqueKeyColumn = $config['unique_key'] ?? null;

        if (empty($uniqueKeyColumn) || ! array_key_exists($uniqueKeyColumn, $rowPayload)) {
            $e = new \InvalidArgumentException("Google Sheets sync failed: unique_key configuration is missing or invalid for model [{$this->modelClass}].");
            Log::error($e->getMessage());
            $this->fail($e);

            return;
        }

        $uniqueValue = trim((string) ($rowPayload[$uniqueKeyColumn] ?? ''));

        if ($uniqueValue === '') {
            $e = new \InvalidArgumentException("Google Sheets sync failed: Resolved unique value is empty for model [{$this->modelClass}] using key [{$uniqueKeyColumn}].");
            Log::error($e->getMessage());
            $this->fail($e);

            return;
        }

        $columnKeys = array_keys($rowPayload);
        $sequentialValues = array_values($rowPayload);

        // 3. Sync to Google Sheets
        try {
            $client->syncRow($sheetName, $columnKeys, $sequentialValues, $uniqueKeyColumn, $uniqueValue);
        } catch (Throwable $e) {
            $this->handleSyncException($e);
        }
    }

    /**
     * Handle client sync exceptions: categorize transient vs permanent errors.
     */
    protected function handleSyncException(Throwable $e): void
    {
        $message = $e->getMessage();
        $code = $e->getCode();

        // 1. Transient exceptions: trigger queue retry back-off
        $isTransient = (
            $code === 429 ||
            str_contains($message, 'quota') ||
            str_contains($message, 'timeout') ||
            str_contains($message, 'connection reset') ||
            str_contains($message, 'Curl error 6') || // DNS resolve failure
            str_contains($message, 'Curl error 7') || // Connect failed
            $code >= 500
        );

        if ($isTransient) {
            Log::warning("Google Sheets sync transient error on [{$this->modelClass}] ID [{$this->modelId}], retrying: ".$message);
            throw $e; // Rethrow to let queue handle retrying
        }

        // 2. Permanent exceptions: log and fail fast
        Log::error("Google Sheets sync permanent error on [{$this->modelClass}] ID [{$this->modelId}]: ".$message);
        $this->fail($e);
    }

    /**
     * Unique identifier components for Laravel ShouldBeUnique deduplication.
     */
    protected function dedupeKeyParts(): array
    {
        return [$this->modelClass, $this->modelId];
    }
}
