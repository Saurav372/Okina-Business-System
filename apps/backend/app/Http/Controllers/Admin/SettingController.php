<?php
 
namespace App\Http\Controllers\Admin;
 
use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
 
class SettingController extends Controller
{
    public function __construct(
        protected SettingsService $settingsService
    ) {}
 
    public function index()
    {
        Gate::authorize('settings.manage');
 
        $groups = [
            'business' => $this->settingsService->all('business'),
            'documents' => $this->settingsService->all('documents'),
            'tax' => $this->settingsService->all('tax'),
            'payments' => $this->settingsService->all('payments'),
        ];
 
        return view('admin.settings.index', compact('groups'));
    }
 
    public function update(Request $request)
    {
        Gate::authorize('settings.manage');
 
        $data = $request->except('_token');
 
        // Explicitly handle enable_gst checkbox if tax settings are posted
        if (isset($data['tax']) && is_array($data['tax'])) {
            $data['tax']['enable_gst'] = isset($data['tax']['enable_gst']) && ($data['tax']['enable_gst'] == '1' || $data['tax']['enable_gst'] == 'true' || $data['tax']['enable_gst'] == 'on');
        } else {
            // Default to not change or explicitly false if needed
        }
 
        foreach ($data as $group => $settings) {
            if (is_array($settings)) {
                foreach ($settings as $key => $value) {
                    try {
                        $this->settingsService->set($group, $key, $value);
                    } catch (\InvalidArgumentException $e) {
                        // Ignore any parameters not defined in schema
                    }
                }
            }
        }
 
        // Clear dashboard cache
        app(DashboardService::class)->clearCache($request->user());
 
        return redirect()->route('admin.settings.index')
            ->with('success', 'Settings updated successfully.');
    }
}
