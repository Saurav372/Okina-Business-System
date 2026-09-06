<?php

namespace App\Http\Resources\Admin;

use App\Support\Health\SystemHealthSummary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property SystemHealthSummary $resource
 */
class SystemHealthResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $components = [];
        foreach ($this->resource->components as $key => $component) {
            $components[$key] = [
                'name' => $component->name,
                'status' => $component->status,
                'latency_ms' => $component->latencyMs,
                'message' => $component->message,
                'metadata' => $component->metadata,
            ];
        }

        return [
            'overall_status' => $this->resource->overallStatus,
            'checked_at' => $this->resource->checkedAt,
            'is_cached' => $this->resource->isCached,
            'cache_age_seconds' => $this->resource->cacheAgeSeconds,
            'warnings' => $this->resource->warnings,
            'components' => $components,
        ];
    }
}
