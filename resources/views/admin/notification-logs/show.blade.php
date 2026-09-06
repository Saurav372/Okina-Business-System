<x-layouts.admin title="Notification Detail">
<div class="py-6 space-y-6 max-w-4xl mx-auto">
    <!-- Header -->
    <div class="border-b border-neutral-200 dark:border-neutral-700 pb-4 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">Notification Log Record</h1>
            <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1 font-mono">Template: {{ $notificationLog['template_key'] ?? 'N/A' }}</p>
        </div>
        <a href="{{ route('admin.notification_logs.index') }}" class="px-4 py-2 bg-neutral-100 hover:bg-neutral-200 text-neutral-700 dark:bg-neutral-700 dark:text-neutral-200 text-xs font-semibold rounded-lg transition-colors">Back to Logs</a>
    </div>

    <!-- Metadata Card -->
    <div class="bg-white dark:bg-neutral-800 rounded-xl p-6 border border-neutral-200 dark:border-neutral-700 shadow-sm grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <div>
            <span class="block text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Event Type</span>
            <span class="text-sm font-bold text-neutral-900 dark:text-white font-mono mt-1 block">{{ $notificationLog['event_type'] }}</span>
        </div>
        <div>
            <span class="block text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Channel</span>
            <span class="text-sm font-semibold text-neutral-900 dark:text-white uppercase tracking-wider mt-1 block">{{ $notificationLog['channel'] }}</span>
        </div>
        <div>
            <span class="block text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Recipient Address</span>
            <span class="text-sm font-mono text-neutral-900 dark:text-white mt-1 block">{{ $notificationLog['recipient_address'] }}</span>
        </div>
        <div>
            <span class="block text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Delivery Status</span>
            <span class="text-sm font-bold uppercase tracking-wider mt-1 block {{ $notificationLog['status'] === 'sent' ? 'text-emerald-600' : 'text-rose-600' }}">{{ $notificationLog['status'] }}</span>
        </div>
        <div>
            <span class="block text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Created At</span>
            <span class="text-sm font-mono text-neutral-900 dark:text-white mt-1 block">{{ $notificationLog['created_at'] }}</span>
        </div>
    </div>

    <!-- Content Preview Card -->
    <div class="bg-white dark:bg-neutral-800 rounded-xl p-6 border border-neutral-200 dark:border-neutral-700 shadow-sm space-y-4">
        <h3 class="text-lg font-bold text-neutral-900 dark:text-white border-b border-neutral-100 dark:border-neutral-700 pb-3">Sanitized Plain Text Content Preview</h3>
        <p class="p-4 bg-neutral-50 dark:bg-neutral-900 text-neutral-800 dark:text-neutral-200 font-mono text-xs rounded-lg whitespace-pre-wrap">{{ $notificationLog['body_summary'] }}</p>
    </div>

    <!-- Attempts Timeline Card -->
    <div class="bg-white dark:bg-neutral-800 rounded-xl p-6 border border-neutral-200 dark:border-neutral-700 shadow-sm space-y-4">
        <h3 class="text-lg font-bold text-neutral-900 dark:text-white border-b border-neutral-100 dark:border-neutral-700 pb-3">Delivery Attempts History</h3>
        <div class="divide-y divide-neutral-100 dark:divide-neutral-700">
            @forelse($notificationLog['attempts'] as $attempt)
                <div class="py-3 flex items-center justify-between text-xs font-mono">
                    <div>
                        <span class="font-bold text-neutral-900 dark:text-white uppercase">{{ $attempt['status'] }}</span>
                        @if($attempt['provider_reference'])
                            <span class="text-neutral-500 ml-2">Ref: {{ $attempt['provider_reference'] }}</span>
                        @endif
                    </div>
                    <span class="text-neutral-500">{{ $attempt['attempted_at'] }}</span>
                </div>
            @empty
                <p class="text-xs text-neutral-500 dark:text-neutral-400 py-2">No delivery attempts recorded.</p>
            @endforelse
        </div>
    </div>
</div>
</x-layouts.admin>
