<x-layouts.admin title="System Health">
<div class="py-6 space-y-6 max-w-6xl mx-auto">
    <!-- Header -->
    <div class="border-b border-neutral-200 dark:border-neutral-700 pb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">System Health & Telemetry</h1>
            <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1">Real-time infrastructure diagnostics, latency metrics, and queue telemetry.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-2xs font-mono text-neutral-500">Checked: {{ $summary->checkedAt }}</span>
            <a href="{{ route('admin.system_health.index') }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg transition-colors">Refresh</a>
        </div>
    </div>

    <!-- Overall Status Banner -->
    <div class="p-6 rounded-xl border flex items-center justify-between shadow-sm
        {{ $summary->overallStatus === 'ok' ? 'bg-emerald-50 border-emerald-200 text-emerald-900 dark:bg-emerald-950 dark:border-emerald-800 dark:text-emerald-100' : '' }}
        {{ $summary->overallStatus === 'degraded' ? 'bg-amber-50 border-amber-200 text-amber-900 dark:bg-amber-950 dark:border-amber-800 dark:text-amber-100' : '' }}
        {{ $summary->overallStatus === 'error' ? 'bg-rose-50 border-rose-200 text-rose-900 dark:bg-rose-950 dark:border-rose-800 dark:text-rose-100' : '' }}">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-full flex items-center justify-center font-bold text-lg shadow-inner
                {{ $summary->overallStatus === 'ok' ? 'bg-emerald-200 text-emerald-800' : 'bg-amber-200 text-amber-800' }}">
                {{ strtoupper(substr($summary->overallStatus, 0, 2)) }}
            </div>
            <div>
                <h2 class="text-lg font-bold uppercase tracking-wider">System Status: {{ $summary->overallStatus }}</h2>
                <p class="text-xs opacity-80 mt-0.5">All core application infrastructure components are being monitored in real time.</p>
            </div>
        </div>
        @if($summary->isCached)
            <span class="px-3 py-1 rounded-full text-2xs font-mono bg-white/60 dark:bg-black/30 font-semibold">Cached ({{ $summary->cacheAgeSeconds }}s ago)</span>
        @endif
    </div>

    <!-- Components Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($summary->components as $key => $component)
            <div class="bg-white dark:bg-neutral-800 rounded-xl p-6 border border-neutral-200 dark:border-neutral-700 shadow-sm space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-neutral-900 dark:text-white uppercase tracking-wider">{{ $component->name }}</h3>
                    <span class="px-2.5 py-0.5 rounded-full text-2xs font-semibold uppercase tracking-wider
                        {{ $component->status === 'ok' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200' : '' }}
                        {{ $component->status === 'degraded' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' : '' }}
                        {{ $component->status === 'error' ? 'bg-rose-100 text-rose-800 dark:bg-rose-900 dark:text-rose-200' : '' }}
                        {{ $component->status === 'unavailable' ? 'bg-neutral-100 text-neutral-600 dark:bg-neutral-700 dark:text-neutral-300' : '' }}">
                        {{ $component->status }}
                    </span>
                </div>

                @if($component->latencyMs !== null)
                    <div class="text-2xl font-bold font-mono text-neutral-900 dark:text-white">
                        {{ $component->latencyMs }} <span class="text-xs font-normal text-neutral-500">ms latency</span>
                    </div>
                @endif

                @if($component->message)
                    <p class="text-xs text-rose-600 dark:text-rose-400 font-medium">{{ $component->message }}</p>
                @endif

                <div class="pt-2 border-t border-neutral-100 dark:border-neutral-700 space-y-1 text-2xs font-mono text-neutral-500 dark:text-neutral-400">
                    @foreach($component->metadata as $mKey => $mVal)
                        <div class="flex justify-between">
                            <span class="capitalize">{{ str_replace('_', ' ', $mKey) }}:</span>
                            <span class="font-semibold text-neutral-800 dark:text-neutral-200">{{ is_bool($mVal) ? ($mVal ? 'Yes' : 'No') : $mVal }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
</x-layouts.admin>
