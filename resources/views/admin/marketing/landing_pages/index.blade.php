<x-layouts.admin title="Landing Pages | Marketing | Okina Craft">
    <x-slot:header>
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-neutral-900">Landing Pages</h1>
                <p class="text-xs text-neutral-500 mt-0.5">Manage B2B advertising landing pages, copy, CTAs, and publication states</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ url('/lp/custom-t-shirts') }}" target="_blank" class="inline-flex items-center gap-2 px-3 py-2 text-xs font-semibold rounded-xl border border-neutral-300 bg-white hover:bg-neutral-50 text-neutral-700 shadow-xs transition-colors">
                    <x-icons.lucide name="lucide-globe" class="w-4 h-4 text-neutral-500" />
                    Live Storefront LP
                    <x-icons.lucide name="lucide-arrow-up-right" class="w-3.5 h-3.5 text-neutral-400" />
                </a>
            </div>
        </div>
    </x-slot:header>

    @if(session('status'))
        <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-2">
            <x-icons.lucide name="lucide-shield-check" class="w-5 h-5 text-emerald-600 flex-shrink-0" />
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <div class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-5 shadow-xs">
                <p class="text-xs font-semibold text-neutral-500 uppercase tracking-wider">Active Landing Pages</p>
                <div class="flex items-baseline justify-between mt-2">
                    <p class="text-2xl font-bold text-neutral-900">{{ $pages->count() }}</p>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                        {{ $pages->where('status', 'published')->count() }} Live
                    </span>
                </div>
            </div>
            <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-5 shadow-xs">
                <p class="text-xs font-semibold text-neutral-500 uppercase tracking-wider">Total Recorded Views</p>
                <p class="text-2xl font-bold text-neutral-900 mt-2">{{ number_format($pages->sum('views_count')) }}</p>
            </div>
            <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-5 shadow-xs">
                <p class="text-xs font-semibold text-neutral-500 uppercase tracking-wider">Total Leads Generated</p>
                <p class="text-2xl font-bold text-neutral-900 mt-2">{{ number_format($pages->sum('leads_count')) }}</p>
            </div>
        </div>

        <div class="bg-white border border-[color:var(--color-border)] rounded-2xl shadow-xs overflow-hidden">
            <div class="p-5 border-b border-neutral-100 flex items-center justify-between">
                <h2 class="text-sm font-bold text-neutral-900 uppercase tracking-wider">Campaign Offer Pages</h2>
            </div>

            <div class="divide-y divide-neutral-100">
                @forelse($pages as $page)
                    <div class="p-6 flex flex-col md:flex-row md:items-center justify-between gap-6 hover:bg-neutral-50/50 transition-colors">
                        <div class="space-y-1.5 max-w-xl">
                            <div class="flex items-center gap-3">
                                <h3 class="text-base font-bold text-neutral-900">{{ $page->title }}</h3>
                                @if($page->isPublished())
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                        ● Published
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                                        ○ Draft
                                    </span>
                                @endif
                            </div>
                            <div class="flex items-center gap-3 text-xs text-neutral-500">
                                <span class="font-mono bg-neutral-100 px-2 py-0.5 rounded text-neutral-700">/lp/{{ $page->slug }}</span>
                                <span>•</span>
                                <span>Last published: {{ $page->published_at ? $page->published_at->diffForHumans() : 'Never' }}</span>
                            </div>
                            <p class="text-xs text-neutral-600 line-clamp-1">
                                {{ $page->hero['title'] ?? 'Custom Corporate & Promotional Apparel' }}
                            </p>
                        </div>

                        <div class="flex items-center gap-6">
                            <div class="text-right hidden sm:block">
                                <p class="text-sm font-bold text-neutral-900">{{ number_format($page->leads_count) }} Leads</p>
                                <p class="text-xs text-neutral-500">{{ number_format($page->views_count) }} Views</p>
                            </div>

                            <div class="flex items-center gap-2">
                                <a href="{{ url('/lp/' . $page->slug) }}" target="_blank" class="px-3 py-2 text-xs font-medium rounded-xl border border-neutral-200 text-neutral-700 hover:bg-neutral-100 transition-colors inline-flex items-center gap-1.5">
                                    <x-icons.lucide name="lucide-globe" class="w-3.5 h-3.5" />
                                    Preview
                                </a>

                                @can('landing_pages.edit')
                                    <a href="{{ route('admin.marketing.landing_pages.edit', $page) }}" class="px-4 py-2 text-xs font-semibold rounded-xl bg-neutral-900 text-white hover:bg-neutral-800 shadow-xs transition-colors inline-flex items-center gap-1.5">
                                        <x-icons.lucide name="lucide-sliders-horizontal" class="w-3.5 h-3.5" />
                                        Edit Content
                                    </a>
                                @endcan

                                @can('landing_pages.publish')
                                    @if($page->isPublished())
                                        <form method="POST" action="{{ route('admin.marketing.landing_pages.unpublish', $page) }}">
                                            @csrf
                                            <button type="submit" onclick="return confirm('Switch page to draft status?')" class="px-3 py-2 text-xs font-medium rounded-xl border border-neutral-200 text-amber-700 hover:bg-amber-50 transition-colors">
                                                Unpublish
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.marketing.landing_pages.publish', $page) }}">
                                            @csrf
                                            <button type="submit" class="px-3 py-2 text-xs font-semibold rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 shadow-xs transition-colors">
                                                Publish Live
                                            </button>
                                        </form>
                                    @endif
                                @endcan
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-12 text-center text-neutral-500 text-sm">
                        No landing pages found.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-layouts.admin>
