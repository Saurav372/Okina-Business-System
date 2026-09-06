<x-layouts.admin title="Notification Log Detail" description="Outbound message inspection, delivery attempts, and transmission audit.">
    <x-slot:header>
        <a href="{{ route('admin.notification_logs.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-neutral-100 hover:bg-neutral-200 text-neutral-700 text-xs font-bold rounded-xl transition-colors shadow-xs">
            <x-icons.lucide name="lucide-arrow-left" class="w-4 h-4" />
            <span>Back to Notification Logs</span>
        </a>
    </x-slot:header>

    <div class="space-y-6 max-w-5xl">
        <!-- Overview Metadata Card -->
        <div class="bg-white rounded-2xl border border-neutral-200 shadow-xs p-6 space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-neutral-100 pb-4">
                <div class="space-y-1">
                    <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Event &amp; Channel</span>
                    <div class="flex items-center gap-2.5">
                        <span class="text-base font-extrabold text-neutral-900 font-mono tracking-tight">{{ $notificationLog['event_type'] ?? 'N/A' }}</span>
                        @php
                            $channel = $notificationLog['channel'] ?? 'other';
                        @endphp
                        @if($channel === 'email')
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-bold uppercase tracking-wider bg-indigo-50 text-indigo-700 border border-indigo-200/80">
                                <x-icons.lucide name="lucide-mail" class="w-3 h-3 text-indigo-500" />
                                <span>Email</span>
                            </span>
                        @elseif($channel === 'sms')
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-bold uppercase tracking-wider bg-sky-50 text-sky-700 border border-sky-200/80">
                                <x-icons.lucide name="lucide-message-square" class="w-3 h-3 text-sky-500" />
                                <span>SMS</span>
                            </span>
                        @elseif($channel === 'whatsapp')
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200/80">
                                <x-icons.lucide name="lucide-message-circle" class="w-3 h-3 text-emerald-600" />
                                <span>WhatsApp</span>
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-bold uppercase tracking-wider bg-neutral-100 text-neutral-700 border border-neutral-200">
                                <span>{{ strtoupper($channel) }}</span>
                            </span>
                        @endif

                        @php
                            $status = $notificationLog['status'] ?? 'pending';
                        @endphp
                        @if($status === 'sent')
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200/80">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                <span>Sent</span>
                            </span>
                        @elseif($status === 'failed')
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider bg-rose-50 text-rose-700 border border-rose-200/80">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                <span>Failed</span>
                            </span>
                        @elseif($status === 'queued')
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider bg-amber-50 text-amber-700 border border-amber-200/80">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                <span>Queued</span>
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider bg-neutral-100 text-neutral-700 border border-neutral-200">
                                <span>{{ ucfirst($status) }}</span>
                            </span>
                        @endif
                    </div>
                </div>
                <div class="text-left sm:text-right space-y-1">
                    <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Timestamp (UTC)</span>
                    <div class="text-xs font-mono font-semibold text-neutral-700">
                        {{ $notificationLog['created_at'] ?? 'N/A' }}
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 text-xs">
                <!-- Recipient -->
                <div class="space-y-1">
                    <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Recipient</span>
                    <div class="font-bold text-neutral-900 text-sm font-mono flex items-center gap-1.5">
                        <x-icons.lucide name="lucide-at-sign" class="w-3.5 h-3.5 text-neutral-400" />
                        <span>{{ $notificationLog['recipient_address'] ?? 'N/A' }}</span>
                    </div>
                    <div class="text-[11px] text-neutral-500 font-mono uppercase">
                        Type: {{ $notificationLog['recipient_type'] ?? 'external' }}
                    </div>
                </div>

                <!-- Template -->
                <div class="space-y-1">
                    <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Template</span>
                    @if(!empty($notificationLog['template']))
                        <div class="font-bold text-neutral-900 font-mono text-xs">
                            {{ $notificationLog['template']['name'] ?? $notificationLog['template_key'] }}
                        </div>
                        <div class="text-[11px] text-neutral-500 font-mono">
                            Key: {{ $notificationLog['template_key'] ?? $notificationLog['template']['template_key'] }}
                        </div>
                    @elseif(!empty($notificationLog['template_key']))
                        <div class="font-bold text-neutral-900 font-mono text-xs">
                            {{ $notificationLog['template_key'] }}
                        </div>
                        <div class="text-[11px] text-neutral-400">Direct Template Key</div>
                    @else
                        <div class="text-neutral-400 font-mono">—</div>
                    @endif
                </div>

                <!-- Delivery Timestamp -->
                <div class="space-y-1">
                    <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Delivery Execution</span>
                    @if(!empty($notificationLog['sent_at']))
                        <div class="font-bold text-emerald-700 font-mono flex items-center gap-1.5">
                            <x-icons.lucide name="lucide-check-circle-2" class="w-3.5 h-3.5 text-emerald-500" />
                            <span>Sent: {{ $notificationLog['sent_at'] }}</span>
                        </div>
                    @elseif(!empty($notificationLog['failed_at']))
                        <div class="font-bold text-rose-700 font-mono flex items-center gap-1.5">
                            <x-icons.lucide name="lucide-alert-triangle" class="w-3.5 h-3.5 text-rose-500" />
                            <span>Failed: {{ $notificationLog['failed_at'] }}</span>
                        </div>
                    @else
                        <div class="text-neutral-500 font-mono">Pending / In Queue</div>
                    @endif
                </div>

                <!-- Log Record ID -->
                <div class="space-y-1">
                    <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Notification Record ID</span>
                    <div class="font-mono text-xs font-bold text-neutral-700 bg-neutral-50 p-2 rounded-xl border border-neutral-200 inline-block">
                        #{{ $notificationLog['id'] ?? 'N/A' }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Preview Card -->
        <div class="bg-white rounded-2xl border border-neutral-200 shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-neutral-200 bg-neutral-50/60 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <x-icons.lucide name="lucide-file-text" class="w-4 h-4 text-neutral-500" />
                    <h3 class="text-xs font-bold uppercase tracking-wider text-neutral-800">Sanitized Message Content</h3>
                </div>
                <span class="text-[10px] text-neutral-400 font-mono">PII-Masked Plain Text</span>
            </div>

            <div class="p-6 space-y-4">
                @if(!empty($notificationLog['subject_rendered']))
                    <div>
                        <span class="block text-[10px] font-bold uppercase tracking-wider text-neutral-400 mb-1">Rendered Subject</span>
                        <div class="text-sm font-semibold text-neutral-900 bg-neutral-50 p-3 rounded-xl border border-neutral-200/70 font-mono">
                            {{ $notificationLog['subject_rendered'] }}
                        </div>
                    </div>
                @endif

                <div>
                    <span class="block text-[10px] font-bold uppercase tracking-wider text-neutral-400 mb-1">Body Summary</span>
                    @if(!empty($notificationLog['body_summary']))
                        <pre class="p-4 bg-neutral-950 text-neutral-200 font-mono text-xs rounded-xl overflow-x-auto shadow-inner border border-neutral-800 leading-relaxed whitespace-pre-wrap">{{ $notificationLog['body_summary'] }}</pre>
                    @else
                        <p class="text-xs text-neutral-400 italic py-2">No body summary recorded for this notification.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Delivery Attempts Timeline Card -->
        <div class="bg-white rounded-2xl border border-neutral-200 shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-neutral-200 bg-neutral-50/60 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <x-icons.lucide name="lucide-history" class="w-4 h-4 text-neutral-500" />
                    <h3 class="text-xs font-bold uppercase tracking-wider text-neutral-800">Delivery Attempts History</h3>
                </div>
                <span class="text-[10px] text-neutral-400 font-mono">Gateway Audits</span>
            </div>

            <div class="p-6">
                @php
                    $attempts = $notificationLog['attempts'] ?? [];
                @endphp

                @if(!empty($attempts) && count($attempts) > 0)
                    <div class="space-y-4">
                        @foreach($attempts as $index => $attempt)
                            <div class="p-4 rounded-xl border border-neutral-200 bg-neutral-50/50 space-y-2">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-mono font-bold text-neutral-500">#{{ $index + 1 }}</span>
                                        @if(($attempt['status'] ?? '') === 'sent')
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                <span class="w-1 h-1 rounded-full bg-emerald-500"></span>
                                                <span>Delivered / Sent</span>
                                            </span>
                                        @elseif(($attempt['status'] ?? '') === 'failed')
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-rose-50 text-rose-700 border border-rose-200">
                                                <span class="w-1 h-1 rounded-full bg-rose-500"></span>
                                                <span>Failed</span>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-neutral-100 text-neutral-700 border border-neutral-200">
                                                <span>{{ ucfirst($attempt['status'] ?? 'attempted') }}</span>
                                            </span>
                                        @endif

                                        @if(!empty($attempt['provider_reference']))
                                            <span class="text-[11px] font-mono text-neutral-500 ml-1">
                                                Ref: <span class="text-neutral-800 font-semibold">{{ $attempt['provider_reference'] }}</span>
                                            </span>
                                        @endif
                                    </div>
                                    <span class="text-xs font-mono text-neutral-500">{{ $attempt['attempted_at'] ?? 'N/A' }}</span>
                                </div>

                                @if(!empty($attempt['error_message']))
                                    <div class="p-2.5 rounded-lg bg-rose-50/80 border border-rose-200 text-rose-800 text-xs font-mono">
                                        <span class="font-bold uppercase text-[10px] text-rose-600 block mb-0.5">Error Details:</span>
                                        {{ $attempt['error_message'] }}
                                    </div>
                                @endif

                                @if(!empty($attempt['response_payload']))
                                    <div class="mt-2">
                                        <span class="text-[10px] font-bold uppercase text-neutral-400 block mb-1">Provider Response Payload</span>
                                        <pre class="p-3 bg-neutral-900 text-neutral-300 font-mono text-[11px] rounded-lg overflow-x-auto border border-neutral-800"><code>{{ json_encode($attempt['response_payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-xs text-neutral-400 italic py-2">No delivery attempts have been recorded for this notification yet.</p>
                @endif
            </div>
        </div>
    </div>
</x-layouts.admin>
