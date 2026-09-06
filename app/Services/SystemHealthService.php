<?php

namespace App\Services;

use App\Support\Health\HealthComponent;
use App\Support\Health\SystemHealthSummary;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class SystemHealthService
{
    private const CACHE_KEY_PREFIX = 'system-health:v1:';

    public function getHealthCheck(): SystemHealthSummary
    {
        $appEnv = (string) config('app.env', 'production');
        $cacheKey = self::CACHE_KEY_PREFIX.$appEnv;

        try {
            $cached = Cache::get($cacheKey);
            if ($cached instanceof SystemHealthSummary) {
                $now = now()->timestamp;
                $checkedTimestamp = CarbonImmutable::parse($cached->checkedAt)->timestamp;
                $cacheAge = max(0, $now - $checkedTimestamp);

                return new SystemHealthSummary(
                    overallStatus: $cached->overallStatus,
                    checkedAt: $cached->checkedAt,
                    isCached: true,
                    cacheAgeSeconds: $cacheAge,
                    warnings: $cached->warnings,
                    components: $cached->components
                );
            }
        } catch (Throwable) {
            // Ignore summary cache retrieval errors and generate fresh telemetry
        }

        $summary = $this->generateSummary();

        try {
            Cache::put($cacheKey, $summary, 30);
        } catch (Throwable) {
            // Ignore summary cache storage errors
        }

        return $summary;
    }

    public function generateSummary(): SystemHealthSummary
    {
        $warnings = [];

        $dbComponent = $this->checkDatabase();
        $cacheComponent = $this->checkCache();
        $storageComponent = $this->checkStorage();
        $queueComponent = $this->checkQueueBacklog();
        $envComponent = $this->checkEnvironment($warnings);

        $components = [
            'database' => $dbComponent,
            'cache' => $cacheComponent,
            'storage' => $storageComponent,
            'queue' => $queueComponent,
            'environment' => $envComponent,
        ];

        $overallStatus = 'ok';
        if ($dbComponent->status === 'error') {
            $overallStatus = 'error';
        } elseif ($cacheComponent->status !== 'ok' || $storageComponent->status !== 'ok' || $envComponent->status !== 'ok') {
            $overallStatus = 'degraded';
        }

        return new SystemHealthSummary(
            overallStatus: $overallStatus,
            checkedAt: now()->toIso8601String(),
            isCached: false,
            cacheAgeSeconds: 0,
            warnings: $warnings,
            components: $components
        );
    }

    private function checkDatabase(): HealthComponent
    {
        $startTime = microtime(true);
        try {
            DB::statement('SELECT 1');
            $latencyMs = round((microtime(true) - $startTime) * 1000, 2);

            return new HealthComponent(
                name: 'Database',
                status: 'ok',
                latencyMs: $latencyMs,
                message: null,
                metadata: [
                    'driver' => config('database.default'),
                ]
            );
        } catch (Throwable) {
            return new HealthComponent(
                name: 'Database',
                status: 'error',
                latencyMs: null,
                message: 'Database connectivity check failed.',
                metadata: [
                    'driver' => config('database.default'),
                ]
            );
        }
    }

    private function checkCache(): HealthComponent
    {
        $startTime = microtime(true);
        $uuid = Str::uuid()->toString();
        $testKey = "healthcheck:{$uuid}";
        $testValue = "test-val-{$uuid}";

        try {
            Cache::put($testKey, $testValue, 10);
            $retrieved = Cache::get($testKey);
            $latencyMs = round((microtime(true) - $startTime) * 1000, 2);

            if ($retrieved !== $testValue) {
                return new HealthComponent(
                    name: 'Cache',
                    status: 'degraded',
                    latencyMs: $latencyMs,
                    message: 'Cache read/write verification failed.',
                    metadata: ['driver' => config('cache.default')]
                );
            }

            return new HealthComponent(
                name: 'Cache',
                status: 'ok',
                latencyMs: $latencyMs,
                message: null,
                metadata: ['driver' => config('cache.default')]
            );
        } catch (Throwable) {
            return new HealthComponent(
                name: 'Cache',
                status: 'degraded',
                latencyMs: null,
                message: 'Cache read/write verification failed.',
                metadata: ['driver' => config('cache.default')]
            );
        } finally {
            try {
                Cache::forget($testKey);
            } catch (Throwable) {
            }
        }
    }

    private function checkStorage(): HealthComponent
    {
        $startTime = microtime(true);
        $diskName = (string) config('filesystems.health_check_disk', 'local');
        $uuid = Str::uuid()->toString();
        $testPath = "health-checks/{$uuid}.tmp";
        $testContent = "health-test-{$uuid}";

        try {
            $disk = Storage::disk($diskName);
            $disk->put($testPath, $testContent);
            $readContent = $disk->get($testPath);
            $latencyMs = round((microtime(true) - $startTime) * 1000, 2);

            if ($readContent !== $testContent) {
                return new HealthComponent(
                    name: 'Storage',
                    status: 'degraded',
                    latencyMs: $latencyMs,
                    message: 'Storage read/write verification failed.',
                    metadata: ['disk' => $diskName]
                );
            }

            return new HealthComponent(
                name: 'Storage',
                status: 'ok',
                latencyMs: $latencyMs,
                message: null,
                metadata: ['disk' => $diskName]
            );
        } catch (Throwable) {
            return new HealthComponent(
                name: 'Storage',
                status: 'degraded',
                latencyMs: null,
                message: 'Storage read/write verification failed.',
                metadata: ['disk' => $diskName]
            );
        } finally {
            try {
                Storage::disk($diskName)->delete($testPath);
            } catch (Throwable) {
            }
        }
    }

    private function checkQueueBacklog(): HealthComponent
    {
        $driver = (string) config('queue.default');

        if ($driver !== 'database') {
            return new HealthComponent(
                name: 'Queue Backlog',
                status: 'unavailable',
                latencyMs: null,
                message: 'Metrics unavailable for configured queue driver.',
                metadata: ['driver' => $driver]
            );
        }

        try {
            $pendingCount = Schema::hasTable('jobs') ? DB::table('jobs')->count() : 0;
            $failedCount = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;

            return new HealthComponent(
                name: 'Queue Backlog',
                status: 'ok',
                latencyMs: null,
                message: null,
                metadata: [
                    'driver' => $driver,
                    'pending_jobs_count' => $pendingCount,
                    'failed_jobs_count' => $failedCount,
                ]
            );
        } catch (Throwable) {
            return new HealthComponent(
                name: 'Queue Backlog',
                status: 'unavailable',
                latencyMs: null,
                message: 'Queue metrics could not be retrieved.',
                metadata: ['driver' => $driver]
            );
        }
    }

    private function checkEnvironment(array &$warnings): HealthComponent
    {
        $appEnv = (string) config('app.env', 'production');
        $debug = (bool) config('app.debug', false);

        $status = 'ok';
        if ($debug && ! in_array($appEnv, ['local', 'testing'], true)) {
            $status = 'degraded';
            $warnings[] = 'APP_DEBUG is enabled in non-local environment.';
        }

        return new HealthComponent(
            name: 'Environment',
            status: $status,
            latencyMs: null,
            message: $status === 'degraded' ? 'Debug mode enabled in non-local environment.' : null,
            metadata: [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'app_environment' => $appEnv,
                'debug_enabled' => $debug,
                'timezone' => config('app.timezone'),
            ]
        );
    }
}
