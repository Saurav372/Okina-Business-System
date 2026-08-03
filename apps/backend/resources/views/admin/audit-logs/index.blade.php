<x-layouts.admin title="Audit Logs">
<div class="py-6 space-y-6">
    <!-- Header -->
    <div class="border-b border-neutral-200 dark:border-neutral-700 pb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">Audit Logs</h1>
            <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1">Immutable security audit trails, entity mutations, and administrative activities.</p>
        </div>
    </div>

    <!-- Filters Bar -->
    <form method="GET" action="{{ route('admin.audit_logs.index') }}" class="bg-white dark:bg-neutral-800 p-4 rounded-xl border border-neutral-200 dark:border-neutral-700 shadow-sm grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <div>
            <label for="action" class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300 uppercase tracking-wider mb-1">Action / Event</label>
            <input type="text" name="action" id="action" value="{{ request('action', $filters->action) }}" placeholder="e.g. profile.updated" class="w-full text-xs rounded-lg border-neutral-300 dark:border-neutral-600 dark:bg-neutral-900 dark:text-white">
        </div>

        <div>
            <label for="module" class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300 uppercase tracking-wider mb-1">Module</label>
            <input type="text" name="module" id="module" value="{{ request('module', $filters->module) }}" placeholder="e.g. profile" class="w-full text-xs rounded-lg border-neutral-300 dark:border-neutral-600 dark:bg-neutral-900 dark:text-white">
        </div>

        <div>
            <label for="subject_type" class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300 uppercase tracking-wider mb-1">Subject Type</label>
            <select name="subject_type" id="subject_type" class="w-full text-xs rounded-lg border-neutral-300 dark:border-neutral-600 dark:bg-neutral-900 dark:text-white">
                <option value="">All Subjects</option>
                @foreach($subjectOptions as $alias => $class)
                    <option value="{{ $alias }}" {{ request('subject_type') === $alias ? 'selected' : '' }}>{{ ucfirst($alias) }}</option>
                @endforeach
            </select>
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
            <a href="{{ route('admin.audit_logs.index') }}" class="px-4 py-2 bg-neutral-100 hover:bg-neutral-200 text-neutral-700 dark:bg-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-600 text-xs font-semibold rounded-lg transition-colors">Reset</a>
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
                        <th class="px-4 py-3">Action / Event</th>
                        <th class="px-4 py-3">Actor</th>
                        <th class="px-4 py-3">Subject</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                    @forelse($logs as $log)
                        <tr class="hover:bg-neutral-50 dark:hover:bg-neutral-700/50">
                            <td class="px-4 py-3 font-mono whitespace-nowrap">{{ $log->occurred_at?->format('Y-m-d H:i:s') }}</td>
                            <td class="px-4 py-3">
                                <span class="font-bold text-neutral-900 dark:text-white font-mono">{{ $log->action }}</span>
                                @if($log->module)
                                    <span class="ml-2 px-2 py-0.5 rounded text-2xs bg-neutral-100 dark:bg-neutral-700 text-neutral-600 dark:text-neutral-300 font-mono">{{ $log->module }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                {{ $log->actorUser?->name ?? $log->actorCustomer?->name ?? $log->actor_label_snapshot ?? 'System' }}
                            </td>
                            <td class="px-4 py-3 font-mono">
                                {{ class_basename($log->subject_type ?? 'N/A') }} #{{ $log->subject_public_id ?? $log->subject_id }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.audit_logs.show', $log->id) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 font-medium">View Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-neutral-500 dark:text-neutral-400">No audit logs found for the selected filters.</td>
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
