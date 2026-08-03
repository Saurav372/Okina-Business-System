<x-layouts.admin title="Notification Logs">
<div class="py-6 space-y-6">
    <!-- Header -->
    <div class="border-b border-neutral-200 dark:border-neutral-700 pb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">Notification Logs</h1>
            <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1">Inspection log of outbound email, SMS, and WhatsApp notifications.</p>
        </div>
    </div>

    <!-- Filters Bar -->
    <form method="GET" action="{{ route('admin.notification_logs.index') }}" class="bg-white dark:bg-neutral-800 p-4 rounded-xl border border-neutral-200 dark:border-neutral-700 shadow-sm grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <div>
            <label for="channel" class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300 uppercase tracking-wider mb-1">Channel</label>
            <select name="channel" id="channel" class="w-full text-xs rounded-lg border-neutral-300 dark:border-neutral-600 dark:bg-neutral-900 dark:text-white">
                <option value="">All Channels</option>
                <option value="email" {{ request('channel') === 'email' ? 'selected' : '' }}>Email</option>
                <option value="sms" {{ request('channel') === 'sms' ? 'selected' : '' }}>SMS</option>
                <option value="whatsapp" {{ request('channel') === 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
            </select>
        </div>

        <div>
            <label for="status" class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300 uppercase tracking-wider mb-1">Status</label>
            <select name="status" id="status" class="w-full text-xs rounded-lg border-neutral-300 dark:border-neutral-600 dark:bg-neutral-900 dark:text-white">
                <option value="">All Statuses</option>
                <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Sent</option>
                <option value="queued" {{ request('status') === 'queued' ? 'selected' : '' }}>Queued</option>
                <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
            </select>
        </div>

        <div>
            <label for="event_type" class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300 uppercase tracking-wider mb-1">Event Type</label>
            <input type="text" name="event_type" id="event_type" value="{{ request('event_type', $filters->eventType) }}" placeholder="e.g. order.confirmed" class="w-full text-xs rounded-lg border-neutral-300 dark:border-neutral-600 dark:bg-neutral-900 dark:text-white">
        </div>

        <div>
            <label for="start_date" class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300 uppercase tracking-wider mb-1">Start Date</label>
            <input type="date" name="start_date" id="start_date" value="{{ request('start_date', $filters->startDate->format('Y-m-d')) }}" class="w-full text-xs rounded-lg border-neutral-300 dark:border-neutral-600 dark:bg-neutral-900 dark:text-white">
        </div>

        <div>
            <label for="end_date" class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300 uppercase tracking-wider mb-1">End Date</label>
            <input type="date" name="end_date" id="end_date" value="{{ request('end_date', $filters->endDate->format('Y-m-d')) }}" class="w-full text-xs rounded-lg border-neutral-300 dark:border-neutral-600 dark:bg-neutral-900 dark:text-white">
        </div>

        <div class="lg:col-span-5 flex justify-end gap-3 pt-2">
            <a href="{{ route('admin.notification_logs.index') }}" class="px-4 py-2 bg-neutral-100 hover:bg-neutral-200 text-neutral-700 dark:bg-neutral-700 dark:text-neutral-200 text-xs font-semibold rounded-lg transition-colors">Reset</a>
            <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg transition-colors">Apply Filters</button>
        </div>
    </form>

    <!-- Table -->
    <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-neutral-600 dark:text-neutral-300">
                <thead class="bg-neutral-50 dark:bg-neutral-900/50 text-neutral-700 dark:text-neutral-300 font-semibold border-b border-neutral-200 dark:border-neutral-700">
                    <tr>
                        <th class="px-4 py-3">Timestamp</th>
                        <th class="px-4 py-3">Channel / Event</th>
                        <th class="px-4 py-3">Recipient</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                    @forelse($logs as $log)
                        <tr class="hover:bg-neutral-50 dark:hover:bg-neutral-700/50">
                            <td class="px-4 py-3 font-mono whitespace-nowrap">{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                            <td class="px-4 py-3">
                                <span class="font-bold text-neutral-900 dark:text-white font-mono">{{ $log->event_type }}</span>
                                <span class="ml-2 px-2 py-0.5 rounded text-2xs bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200 uppercase tracking-wider font-semibold">{{ $log->channel }}</span>
                            </td>
                            <td class="px-4 py-3 font-mono">
                                {{ \App\Support\Notification\NotificationContentSanitizer::maskAddress($log->recipient_address) }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded-full text-2xs font-semibold uppercase tracking-wider
                                    {{ $log->status === 'sent' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200' : '' }}
                                    {{ $log->status === 'failed' ? 'bg-rose-100 text-rose-800 dark:bg-rose-900 dark:text-rose-200' : '' }}
                                    {{ $log->status === 'queued' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' : '' }}">
                                    {{ $log->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.notification_logs.show', $log->id) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 font-medium">View Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-neutral-500 dark:text-neutral-400">No notification logs found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
            <div class="px-4 py-3 border-t border-neutral-200 dark:border-neutral-700">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
</x-layouts.admin>
