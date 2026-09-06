<x-layouts.admin title="Audit Log Detail" description="Immutable security audit trail record and event payload snapshot.">
    <x-slot:header>
        <a href="{{ route('admin.audit_logs.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-neutral-100 hover:bg-neutral-200 text-neutral-700 text-xs font-bold rounded-xl transition-colors shadow-xs">
            <x-icons.lucide name="lucide-arrow-left" class="w-4 h-4" />
            <span>Back to Audit Logs</span>
        </a>
    </x-slot:header>

    <div class="space-y-6 max-w-5xl">
        <!-- Event Overview Metadata Grid -->
        <div class="bg-white rounded-2xl border border-neutral-200 shadow-xs p-6 space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-neutral-100 pb-4">
                <div class="space-y-1">
                    <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Event Action</span>
                    <div class="flex items-center gap-2.5">
                        <span class="text-base font-extrabold text-neutral-900 font-mono tracking-tight">{{ $auditLog['action'] }}</span>
                        @if(!empty($auditLog['module']))
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-neutral-100 text-neutral-600 border border-neutral-200 font-mono">
                                {{ $auditLog['module'] }}
                            </span>
                        @endif
                    </div>
                </div>
                <div class="text-left sm:text-right space-y-1">
                    <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Timestamp (UTC)</span>
                    <div class="text-xs font-mono font-semibold text-neutral-700">
                        {{ $auditLog['occurred_at'] ?? 'N/A' }}
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 text-xs">
                <!-- Actor -->
                <div class="space-y-1">
                    <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Triggered By (Actor)</span>
                    <div class="font-bold text-neutral-900 text-sm flex items-center gap-1.5">
                        <x-icons.lucide name="lucide-user" class="w-3.5 h-3.5 text-neutral-400" />
                        <span>{{ $auditLog['actor']['name'] ?? 'System' }}</span>
                    </div>
                    <div class="text-[11px] text-neutral-500 font-mono uppercase">
                        Type: {{ $auditLog['actor']['type'] ?? 'system' }}
                        @if(!empty($auditLog['actor']['user_id']))
                            (ID: {{ $auditLog['actor']['user_id'] }})
                        @endif
                    </div>
                </div>

                <!-- Subject -->
                <div class="space-y-1">
                    <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Target Entity (Subject)</span>
                    @php
                        $subjectType = $auditLog['subject']['type'] ?? null;
                        $subjectId = $auditLog['subject']['public_id'] ?? $auditLog['subject']['id'] ?? ($auditLog['metadata']['public_id'] ?? null);
                    @endphp
                    @if($subjectType || $subjectId)
                        <div class="font-bold text-neutral-900 font-mono">
                            {{ class_basename($subjectType ?? 'Entity') }}
                        </div>
                        <div class="text-[11px] text-neutral-500 font-mono">
                            #{{ $subjectId ?? 'N/A' }}
                        </div>
                    @else
                        <div class="text-neutral-400 font-mono">—</div>
                    @endif
                </div>

                <!-- IP Address -->
                <div class="space-y-1">
                    <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Client IP Address</span>
                    <div class="font-bold text-neutral-800 font-mono flex items-center gap-1.5">
                        <x-icons.lucide name="lucide-globe" class="w-3.5 h-3.5 text-neutral-400" />
                        <span>{{ $auditLog['ip_address'] ?? 'Internal / CLI' }}</span>
                    </div>
                </div>

                <!-- Event UUID -->
                <div class="space-y-1">
                    <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Audit Event UUID</span>
                    <div class="font-mono text-[11px] text-neutral-600 break-all select-all bg-neutral-50 p-1.5 rounded-lg border border-neutral-200">
                        {{ $auditLog['event_id'] }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Event Payload & Metadata Inspection -->
        <div class="bg-white rounded-2xl border border-neutral-200 shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-neutral-200 bg-neutral-50/60 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <x-icons.lucide name="lucide-code-2" class="w-4 h-4 text-neutral-500" />
                    <h3 class="text-xs font-bold uppercase tracking-wider text-neutral-800">Sanitized Event Metadata &amp; Payload</h3>
                </div>
                <span class="text-[10px] text-neutral-400 font-mono">JSON Snapshot</span>
            </div>

            <div class="p-6">
                @php
                    $payloadData = !empty($auditLog['metadata']) 
                        ? $auditLog['metadata'] 
                        : (!empty($auditLog['new_values']) 
                            ? ['new_values' => $auditLog['new_values'], 'old_values' => $auditLog['old_values'] ?? null] 
                            : []);
                @endphp

                @if(!empty($payloadData))
                    <pre class="p-4 bg-neutral-950 text-emerald-400 font-mono text-xs rounded-xl overflow-x-auto shadow-inner border border-neutral-800 leading-relaxed"><code>{{ json_encode($payloadData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                @else
                    <p class="text-xs text-neutral-400 italic py-4">No additional metadata payload was recorded for this event.</p>
                @endif
            </div>
        </div>
    </div>
</x-layouts.admin>
