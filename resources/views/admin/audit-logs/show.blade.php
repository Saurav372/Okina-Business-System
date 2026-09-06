<x-layouts.admin title="Audit Log Detail">
<div class="py-6 space-y-6 max-w-4xl mx-auto">
    <!-- Header -->
    <div class="border-b border-neutral-200 dark:border-neutral-700 pb-4 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">Audit Log Record</h1>
            <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1 font-mono">Event ID: {{ $auditLog['event_id'] }}</p>
        </div>
        <a href="{{ route('admin.audit_logs.index') }}" class="px-4 py-2 bg-neutral-100 hover:bg-neutral-200 text-neutral-700 dark:bg-neutral-700 dark:text-neutral-200 text-xs font-semibold rounded-lg transition-colors">Back to Logs</a>
    </div>

    <!-- Metadata Card -->
    <div class="bg-white dark:bg-neutral-800 rounded-xl p-6 border border-neutral-200 dark:border-neutral-700 shadow-sm grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <div>
            <span class="block text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Action / Event</span>
            <span class="text-sm font-bold text-neutral-900 dark:text-white font-mono mt-1 block">{{ $auditLog['action'] }}</span>
        </div>
        <div>
            <span class="block text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Actor</span>
            <span class="text-sm font-semibold text-neutral-900 dark:text-white mt-1 block">{{ $auditLog['actor']['name'] }} ({{ $auditLog['actor']['type'] }})</span>
        </div>
        <div>
            <span class="block text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Timestamp</span>
            <span class="text-sm font-mono text-neutral-900 dark:text-white mt-1 block">{{ $auditLog['occurred_at'] }}</span>
        </div>
        <div>
            <span class="block text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Subject</span>
            <span class="text-sm font-mono text-neutral-900 dark:text-white mt-1 block">{{ class_basename($auditLog['subject']['type'] ?? 'N/A') }} #{{ $auditLog['subject']['public_id'] ?? $auditLog['subject']['id'] }}</span>
        </div>
        <div>
            <span class="block text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">IP Address</span>
            <span class="text-sm font-mono text-neutral-900 dark:text-white mt-1 block">{{ $auditLog['ip_address'] ?? 'N/A' }}</span>
        </div>
    </div>

    <!-- Sanitized Payload Inspection Card -->
    <div class="bg-white dark:bg-neutral-800 rounded-xl p-6 border border-neutral-200 dark:border-neutral-700 shadow-sm space-y-4">
        <h3 class="text-lg font-bold text-neutral-900 dark:text-white border-b border-neutral-100 dark:border-neutral-700 pb-3">Sanitized Event Metadata & Payload</h3>
        <pre class="p-4 bg-neutral-900 text-emerald-400 font-mono text-xs rounded-lg overflow-x-auto"><code>{{ json_encode($auditLog['metadata'] ?? $auditLog['new_values'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
    </div>
</div>
</x-layouts.admin>
