<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\SystemHealthResource;
use App\Services\SystemHealthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SystemHealthController extends Controller
{
    public function __construct(
        protected SystemHealthService $healthService
    ) {}

    /**
     * Display system health telemetry dashboard (HTML Blade or JSON Resource).
     */
    public function index(Request $request): mixed
    {
        Gate::authorize('system.health.view');

        $summary = $this->healthService->getHealthCheck();

        if ($request->wantsJson()) {
            return new SystemHealthResource($summary);
        }

        return view('admin.system-health.index', [
            'summary' => $summary,
        ]);
    }
}
