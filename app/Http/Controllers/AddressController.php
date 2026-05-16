<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Support\TeamLeaderRoutes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AddressController extends Controller
{
    public function index()
    {
        if (TeamLeaderRoutes::isTeamLeader(Auth::user())) {
            return redirect()->route('team-leader.addresses.index');
        }

        $addresses = Auth::user()->addresses;

        return view('account.addresses.index', compact('addresses'));
    }

    public function create()
    {
        if (TeamLeaderRoutes::isTeamLeader(Auth::user())) {
            return redirect()->route('team-leader.addresses.create');
        }

        return view('account.addresses.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'address' => 'required|string',
            'city' => 'required|string',
            'state' => 'nullable|string',
            'zip' => 'nullable|string',
            'country' => 'required|string',
            'type' => 'required|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        Auth::user()->addresses()->create($validated);

        return redirect()->route(TeamLeaderRoutes::addressesIndex())->with('success', 'Address added successfully.');
    }

    public function edit(Address $address)
    {
        if ($address->user_id !== Auth::id()) {
            abort(403);
        }

        if (TeamLeaderRoutes::isTeamLeader(Auth::user())) {
            return redirect()->route('team-leader.addresses.edit', $address);
        }

        return view('account.addresses.edit', compact('address'));
    }

    public function update(Request $request, Address $address)
    {
        if ($address->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'address' => 'required|string',
            'city' => 'required|string',
            'state' => 'nullable|string',
            'zip' => 'nullable|string',
            'country' => 'required|string',
            'type' => 'required|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $address->update($validated);

        return redirect()->route(TeamLeaderRoutes::addressesIndex())->with('success', 'Address updated successfully.');
    }

    public function destroy(Address $address)
    {
        if ($address->user_id !== Auth::id()) {
            abort(403);
        }
        $address->delete();

        return redirect()->route(TeamLeaderRoutes::addressesIndex())->with('success', 'Address deleted successfully.');
    }
}
