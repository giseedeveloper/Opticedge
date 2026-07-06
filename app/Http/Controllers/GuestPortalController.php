<?php

namespace App\Http\Controllers;

use App\Models\GuestVendorInvitation;
use App\Models\User;
use App\Services\GuestVendorInvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class GuestPortalController extends Controller
{
    public function dashboard(Request $request): View
    {
        $user = $request->user();
        $pendingCount = GuestVendorInvitation::query()
            ->where('guest_user_id', $user->id)
            ->where('status', GuestVendorInvitation::STATUS_PENDING)
            ->count();

        return view('guest.dashboard', compact('user', 'pendingCount'));
    }

    public function requests(Request $request): View
    {
        $invitations = GuestVendorInvitation::query()
            ->where('guest_user_id', $request->user()->id)
            ->where('status', GuestVendorInvitation::STATUS_PENDING)
            ->with(['tenant:id,name,brand_name', 'inviter:id,name'])
            ->orderByDesc('created_at')
            ->get();

        return view('guest.requests', compact('invitations'));
    }

    public function profile(Request $request): View
    {
        return view('guest.profile', ['user' => $request->user()]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:100',
        ]);

        User::withoutGlobalScopes()
            ->whereKey($request->user()->id)
            ->update($validated);

        return redirect()->route('guest.profile')->with('success', 'Profile updated.');
    }

    public function acceptInvitation(Request $request, GuestVendorInvitation $invitation, GuestVendorInvitationService $service): RedirectResponse
    {
        $assigned = $service->accept($invitation, $request->user());

        return match ($assigned->role) {
            'agent' => redirect()->route('agent.dashboard')->with('success', 'You joined the vendor as an agent.'),
            'teamleader' => redirect()->route('team-leader.dashboard')->with('success', 'You joined the vendor as a team leader.'),
            'regional_manager' => redirect()->route('regional-manager.dashboard')->with('success', 'You joined the vendor as a regional manager.'),
            default => redirect()->route('dashboard')->with('success', 'You joined the vendor successfully.'),
        };
    }

    public function declineInvitation(Request $request, GuestVendorInvitation $invitation, GuestVendorInvitationService $service): RedirectResponse
    {
        $service->decline($invitation, $request->user());

        return redirect()->route('guest.requests')->with('success', 'Invitation declined.');
    }
}
