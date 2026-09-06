<x-layouts.admin title="Audit Logs" description="Immutable security audit trails, entity mutations, and administrative activities.">
    <div class="space-y-6">
        <!-- Filters Bar -->
        <div class="bg-white rounded-2xl border border-neutral-200 shadow-xs p-5">
            <form method="GET" action="{{ route('admin.audit_logs.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    <div>
                        <label for="action" class="block text-xs font-bold uppercase tracking-wider text-neutral-600 mb-1.5">Action / Event</label>
                        <input type="text" name="action" id="action" value="{{ request('action', $filters->action) }}" placeholder="e.g. order.cancelled" class="w-full text-xs rounded-xl border-neutral-300 bg-white text-neutral-800 focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 py-2.5 font-mono">
                    </div>

                    <div>
                        <label for="module" class="block text-xs font-bold uppercase tracking-wider text-neutral-600 mb-1.5">Module</label>
                        <input type="text" name="module" id="module" value="{{ request('module', $filters->module) }}" placeholder="e.g. orders, finance" class="w-full text-xs rounded-xl border-neutral-300 bg-white text-neutral-800 focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 py-2.5 font-mono">
                    </div>

                    <div>
                        <label for="subject_type" class="block text-xs font-bold uppercase tracking-wider text-neutral-600 mb-1.5">Subject Type</label>
                        <select name="subject_type" id="subject_type" class="w-full text-xs font-semibold rounded-xl border-neutral-300 bg-white text-neutral-800 focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 py-2.5">
                            <option value="">All Subjects</option>
                            @foreach($subjectOptions as $alias => $class)
                                <option value="{{ $alias }}" {{ request('subject_type') === $alias ? 'selected' : '' }}>{{ ucfirst($alias) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="start_date" class="block text-xs font-bold uppercase tracking-wider text-neutral-600 mb-1.5">Start Date</label>
                        <input type="date" name="start_date" id="start_date" value="{{ request('start_date', $filters->startDate->format('Y-m-d')) }}" class="w-full text-xs font-medium rounded-xl border-neutral-300 bg-white text-neutral-800 focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 py-2">
                    </div>

                    <div>
                        <label for="end_date" class="block text-xs font-bold uppercase tracking-wider text-neutral-600 mb-1.5">End Date</label>
                        <input type="date" name="end_date" id="end_date" value="{{ request('end_date', $filters->endDate->format('Y-m-d')) }}" class="w-full text-xs font-medium rounded-xl border-neutral-300 bg-white text-neutral-800 focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 py-2">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-neutral-100">
                    @if(request()->hasAny(['action', 'module', 'subject_type', 'start_date', 'end_date']))
                        <a href="{{ route('admin.audit_logs.index') }}" class="px-4 py-2 bg-neutral-100 hover:bg-neutral-200 text-neutral-700 text-xs font-bold rounded-xl transition-colors">
                            Reset Filters
                        </a>
                    @endif
                    <button type="submit" class="px-5 py-2 bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-bold rounded-xl transition-colors shadow-xs">
                        Apply Filters
                    </button>
                </div>
            </form>
        </div>

        <!-- Audit Logs Table -->
        <div class="bg-white rounded-2xl border border-neutral-200 shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-neutral-200 bg-neutral-50/50 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <x-icons.lucide name="lucide-shield-check" class="w-4 h-4 text-neutral-500" />
                    <h2 class="text-xs font-bold uppercase tracking-wider text-neutral-800">Security Audit Trail</h2>
                </div>
                <span class="text-[11px] text-neutral-400 font-medium">Immutable log entries</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-neutral-50 text-[10px] uppercase font-bold text-neutral-500 border-b border-neutral-200">
                        <tr>
                            <th class="px-6 py-3 font-semibold">Timestamp (UTC)</th>
                            <th class="px-6 py-3 font-semibold">Action / Event</th>
                            <th class="px-6 py-3 font-semibold">Actor</th>
                            <th class="px-6 py-3 font-semibold">Target Subject</th>
                            <th class="px-6 py-3 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100 text-xs">
                        @forelse($logs as $log)
                            @php
                                $subjectName = class_basename($log->subject_type ?? '');
                                $subjectId = $log->subject_public_id ?? $log->subject_id ?? ($log->metadata['public_id'] ?? null);
                            @endphp
                            <tr class="hover:bg-neutral-50/70 transition-colors">
                                <td class="px-6 py-3.5 font-mono text-neutral-600 whitespace-nowrap">
                                    {{ $log->occurred_at?->format('Y-m-d H:i:s') }}
                                </td>
                                <td class="px-6 py-3.5">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono font-bold text-neutral-900 bg-neutral-100 px-2 py-0.5 rounded-md border border-neutral-200/80">
                                            {{ $log->action }}
                                        </span>
                                        @if($log->module)
                                            <span class="text-[10px] uppercase font-bold tracking-wider text-neutral-500 font-mono">
                                                {{ $log->module }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-3.5">
                                    <div class="flex items-center gap-1.5 font-semibold text-neutral-800">
                                        <x-icons.lucide name="lucide-user" class="w-3.5 h-3.5 text-neutral-400" />
                                        <span>{{ $log->actorUser?->name ?? $log->actorCustomer?->name ?? $log->actor_label_snapshot ?? 'System' }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-3.5 font-mono">
                                    @if($subjectName && $subjectName !== 'unknown' && $subjectName !== 'N/A')
                                        <span class="font-semibold text-neutral-800">{{ $subjectName }}</span>
                                        @if($subjectId)
                                            <span class="text-neutral-500 font-medium">#{{ $subjectId }}</span>
                                        @endif
                                    @elseif($subjectId)
                                        <span class="text-neutral-700 font-medium">#{{ $subjectId }}</span>
                                    @else
                                        <span class="text-neutral-400">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3.5 text-right">
                                    <a href="{{ route('admin.audit_logs.show', $log->id) }}" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold text-neutral-700 hover:text-neutral-900 bg-neutral-100 hover:bg-neutral-200 rounded-lg transition-colors">
                                        <span>View Detail</span>
                                        <x-icons.lucide name="lucide-chevron-right" class="w-3 h-3 text-neutral-400" />
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-neutral-400 font-medium">
                                    No audit logs found for the selected filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($logs->hasPages())
                <div class="px-6 py-4 border-t border-neutral-200 bg-neutral-50/50">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts.admin>
