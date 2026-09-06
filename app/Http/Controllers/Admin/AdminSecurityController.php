<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAdminPasswordRequest;
use App\Services\AdminPasswordService;
use App\Services\AdminSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminSecurityController extends Controller
{
    public function __construct(
        protected AdminPasswordService $passwordService,
        protected AdminSessionService $sessionService
    ) {}

    /**
     * Render security settings view with active browser sessions.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();
        $currentSessionId = $request->session()->getId();
        $sessions = $this->sessionService->getActiveSessions($user, $currentSessionId);

        return view('admin.security', [
            'user' => $user,
            'sessions' => $sessions,
        ]);
    }

    /**
     * Update user password, rotate current session ID, and revoke other sessions.
     */
    public function updatePassword(UpdateAdminPasswordRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();
        $currentSessionId = $request->session()->getId();

        $this->passwordService->changePassword($user, $validated['password'], $currentSessionId);

        // Regenerate current session ID & CSRF token to prevent session fixation
        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.security')->with('status', 'Password updated successfully.');
    }
}
