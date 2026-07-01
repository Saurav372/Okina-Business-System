<?php

namespace App\Observers;

use App\Jobs\SyncRecordToGoogleSheetsJob;
use App\Models\GoogleSheetsSyncLog;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

class GoogleSheetsSyncObserver implements ShouldHandleEventsAfterCommit
{
    /**
     * Handle the Model "saved" event.
     */
    public function saved(Model $model): void
    {
        if (Config::get('sheets.enabled', false)) {
            $log = GoogleSheetsSyncLog::create([
                'model_class' => get_class($model),
                'model_id' => $model->getKey(),
                'unique_key' => 'pending',
                'unique_value' => 'pending',
                'status' => GoogleSheetsSyncLog::STATUS_QUEUED,
                'triggered_by' => 'automatic',
                'payload_hash' => '',
            ]);

            SyncRecordToGoogleSheetsJob::dispatch(
                get_class($model),
                $model->getKey(),
                $log->id,
                'automatic'
            );
        }
    }
}
