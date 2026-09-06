<x-layouts.admin title="Notification Logs" description="Inspection log of outbound email, SMS, and WhatsApp notifications.">
    <div class="space-y-6">
        <!-- Filters Bar -->
        <div class="bg-white rounded-2xl border border-neutral-200 shadow-xs p-5">
            <form method="GET" action="{{ route('admin.notification_logs.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    <div>
                        <label for="channel" class="block text-xs font-bold uppercase tracking-wider text-neutral-600 mb-1.5">Channel</label>
                        <select name="channel" id="channel" class="w-full text-xs font-semibold rounded-xl border-neutral-300 bg-white text-neutral-800 focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 py-2.5">
                            <option value="">All Channels</option>
                            <option value="email" {{ request('channel') === 'email' ? 'selected' : '' }}>Email</option>
                            <option value="sms" {{ request('channel') === 'sms' ? 'selected' : '' }}>SMS</option>
                            <option value="whatsapp" {{ request('channel') === 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                        </select>
                    </div>

                    <div>
                        <label for="status" class="block text-xs font-bold uppercase tracking-wider text-neutral-600 mb-1.5">Status</label>
                        <select name="status" id="status" class="w-full text-xs font-semibold rounded-xl border-neutral-300 bg-white text-neutral-800 focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 py-2.5">
                            <option value="">All Statuses</option>
                            <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Sent</option>
                            <option value="queued" {{ request('status') === 'queued' ? 'selected' : '' }}>Queued</option>
                            <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                        </select>
                    </div>

                    <div>
                        <label for="event_type" class="block text-xs font-bold uppercase tracking-wider text-neutral-600 mb-1.5">Event Type</label>
                        <input type="text" name="event_type" id="event_type" value="{{ request('event_type', $filters->eventType) }}" placeholder="e.g. order.confirmed" class="w-full text-xs rounded-xl border-neutral-300 bg-white text-neutral-800 focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 py-2.5 font-mono">
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

                <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-neutral-100">
                    @if(request()->hasAny(['channel', 'status', 'event_type', 'start_date', 'end_date']))
                        <a href="{{ route('admin.notification_logs.index') }}" class="px-4 py-2 bg-neutral-100 hover:bg-neutral-200 text-neutral-700 text-xs font-bold rounded-xl transition-colors">
                            Reset Filters
                        </a>
                    @endif
                    <button type="submit" class="px-5 py-2 bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-bold rounded-xl transition-colors shadow-xs">
                        Apply Filters
                    </button>
                </div>
            </form>
        </div>

        <!-- Transmission Logs Table -->
        <div class="bg-white rounded-2xl border border-neutral-200 shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-neutral-200 bg-neutral-50/50 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <x-icons.lucide name="lucide-send" class="w-4 h-4 text-neutral-500" />
                    <h2 class="text-xs font-bold uppercase tracking-wider text-neutral-800">Transmission History</h2>
                </div>
                <span class="text-[11px] text-neutral-400 font-medium">Outbound email, SMS, and WhatsApp dispatches</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-neutral-50 text-[10px] uppercase font-bold text-neutral-500 border-b border-neutral-200">
                        <tr>
                            <th class="px-6 py-3 font-semibold">Timestamp (UTC)</th>
                            <th class="px-6 py-3 font-semibold">Channel & Event</th>
                            <th class="px-6 py-3 font-semibold">Recipient</th>
                            <th class="px-6 py-3 font-semibold">Status</th>
                            <th class="px-6 py-3 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100 text-xs">
                        @forelse($logs as $log)
                            <tr class="hover:bg-neutral-50/70 transition-colors">
                                <td class="px-6 py-3.5 font-mono text-neutral-600 whitespace-nowrap">
                                    {{ $log->created_at?->format('Y-m-d H:i:s') }}
                                </td>
                                <td class="px-6 py-3.5">
                                    <div class="flex items-center gap-2">
                                        @if($log->channel === 'email')
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-bold uppercase tracking-wider bg-indigo-50 text-indigo-700 border border-indigo-200/80">
                                                <x-icons.lucide name="lucide-mail" class="w-3 h-3 text-indigo-500" />
                                                <span>Email</span>
                                            </span>
                                        @elseif($log->channel === 'sms')
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-bold uppercase tracking-wider bg-sky-50 text-sky-700 border border-sky-200/80">
                                                <x-icons.lucide name="lucide-message-square" class="w-3 h-3 text-sky-500" />
                                                <span>SMS</span>
                                            </span>
                                        @elseif($log->channel === 'whatsapp')
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200/80">
                                                <x-icons.lucide name="lucide-message-circle" class="w-3 h-3 text-emerald-600" />
                                                <span>WhatsApp</span>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-bold uppercase tracking-wider bg-neutral-100 text-neutral-700 border border-neutral-200">
                                                <span>{{ strtoupper($log->channel) }}</span>
                                            </span>
                                        @endif
                                        <span class="font-mono font-bold text-neutral-900 bg-neutral-100 px-2 py-0.5 rounded-md border border-neutral-200/80 text-xs">
                                            {{ $log->event_type }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-3.5 font-mono text-neutral-700">
                                    <div class="flex items-center gap-1.5">
                                        <x-icons.lucide name="lucide-at-sign" class="w-3.5 h-3.5 text-neutral-400" />
                                        <span>{{ \App\Support\Notification\NotificationContentSanitizer::maskAddress($log->recipient_address) }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-3.5">
                                    @if($log->status === 'sent')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200/80">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            <span>Sent</span>
                                        </span>
                                    @elseif($log->status === 'failed')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider bg-rose-50 text-rose-700 border border-rose-200/80">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                            <span>Failed</span>
                                        </span>
                                    @elseif($log->status === 'queued')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider bg-amber-50 text-amber-700 border border-amber-200/80">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                            <span>Queued</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider bg-neutral-100 text-neutral-700 border border-neutral-200">
                                            <span>{{ ucfirst($log->status) }}</span>
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-3.5 text-right">
                                    <a href="{{ route('admin.notification_logs.show', $log->id) }}" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold text-neutral-700 hover:text-neutral-900 bg-neutral-100 hover:bg-neutral-200 rounded-lg transition-colors">
                                        <span>View Detail</span>
                                        <x-icons.lucide name="lucide-chevron-right" class="w-3 h-3 text-neutral-400" />
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-neutral-400">
                                    <div class="flex flex-col items-center justify-center space-y-2">
                                        <div class="w-10 h-10 rounded-full bg-neutral-100 flex items-center justify-center text-neutral-400">
                                            <x-icons.lucide name="lucide-send" class="w-5 h-5" />
                                        </div>
                                        <p class="text-sm font-semibold text-neutral-700">No notification logs found</p>
                                        <p class="text-xs text-neutral-400">Outbound transmission logs will appear here once notifications are sent.</p>
                                    </div>
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
