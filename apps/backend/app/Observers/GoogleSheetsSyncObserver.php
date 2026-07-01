<?php

namespace App\Observers;

use App\Jobs\SyncRecordToGoogleSheetsJob;
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
            SyncRecordToGoogleSheetsJob::dispatch(get_class($model), $model->getKey());
        }
    }
}
