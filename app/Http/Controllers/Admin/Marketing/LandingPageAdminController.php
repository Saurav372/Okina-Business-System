<?php

namespace App\Http\Controllers\Admin\Marketing;

use App\Http\Controllers\Controller;
use App\Models\LandingLead;
use App\Models\LandingPage;
use App\Models\MarketingEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class LandingPageAdminController extends Controller
{
    public function index(): View
    {
        Gate::authorize('landing_pages.view');

        // Ensure default landing pages exist
        LandingPage::firstOrCreate(
            ['slug' => 'custom-t-shirts'],
            LandingPage::defaultContentFor('custom-t-shirts')
        );

        LandingPage::firstOrCreate(
            ['slug' => 'bulk-printing'],
            [
                'title' => 'Get Bulk Printing for Your Business',
                'status' => 'published',
                'published_at' => now(),
            ]
        );

        $pages = LandingPage::query()->latest()->get()->map(function ($page) {
            $page->leads_count = LandingLead::query()
                ->where(function ($q) use ($page) {
                    $q->where('source', 'LIKE', "%{$page->slug}%")
                        ->orWhere('page_url', 'LIKE', "%{$page->slug}%");
                    if ($page->slug === 'custom-t-shirts') {
                        $q->orWhere('source', 'meta');
                    }
                    if ($page->slug === 'bulk-printing') {
                        $q->orWhere('source', 'okina-craft-landing-page');
                    }
                })->count();
            $page->views_count = MarketingEvent::query()->where('landing_page_slug', $page->slug)->where('event_name', 'LandingPageView')->count();
            return $page;
        });

        return view('admin.marketing.landing_pages.index', [
            'pages' => $pages,
        ]);
    }

    public function edit(LandingPage $landingPage): View
    {
        Gate::authorize('landing_pages.edit');

        return view('admin.marketing.landing_pages.edit', [
            'page' => $landingPage,
        ]);
    }

    public function update(Request $request, LandingPage $landingPage): RedirectResponse
    {
        Gate::authorize('landing_pages.edit');

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'hero' => ['nullable', 'array'],
            'showcase' => ['nullable', 'array'],
            'use_cases' => ['nullable', 'array'],
            'techniques' => ['nullable', 'array'],
            'tagline' => ['nullable', 'array'],
            'benefits' => ['nullable', 'array'],
            'steps' => ['nullable', 'array'],
            'gallery' => ['nullable', 'array'],
            'faq' => ['nullable', 'array'],
            'cta_settings' => ['nullable', 'array'],
            'section_order' => ['nullable', 'array'],
        ]);

        $landingPage->update([
            'title' => $validated['title'],
            'hero' => $validated['hero'] ?? $landingPage->hero,
            'showcase' => $validated['showcase'] ?? $landingPage->showcase,
            'use_cases' => $validated['use_cases'] ?? $landingPage->use_cases,
            'techniques' => $validated['techniques'] ?? $landingPage->techniques,
            'tagline' => $validated['tagline'] ?? $landingPage->tagline,
            'benefits' => $validated['benefits'] ?? $landingPage->benefits,
            'steps' => $validated['steps'] ?? $landingPage->steps,
            'gallery' => $validated['gallery'] ?? $landingPage->gallery,
            'faq' => $validated['faq'] ?? $landingPage->faq,
            'cta_settings' => $validated['cta_settings'] ?? $landingPage->cta_settings,
            'section_order' => $validated['section_order'] ?? $landingPage->section_order,
        ]);

        return redirect()->route('admin.marketing.landing_pages.edit', $landingPage)
            ->with('status', 'Landing page content updated successfully.');
    }

    public function publish(LandingPage $landingPage): RedirectResponse
    {
        Gate::authorize('landing_pages.publish');

        $landingPage->publish();

        return back()->with('status', "Landing page '{$landingPage->title}' published live.");
    }

    public function unpublish(LandingPage $landingPage): RedirectResponse
    {
        Gate::authorize('landing_pages.publish');

        $landingPage->unpublish();

        return back()->with('status', "Landing page '{$landingPage->title}' set to draft mode.");
    }

    public function uploadMedia(Request $request): JsonResponse
    {
        Gate::authorize('landing_page_media.upload');

        $request->validate([
            'file' => ['required', 'image', 'max:10240'], // 10MB max
        ]);

        $path = $request->file('file')->store('landing_media', 'public');
        $url = Storage::url($path);

        return response()->json([
            'success' => true,
            'url' => $url,
            'path' => $path,
        ]);
    }
}
