<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class DealerController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->string('search')->trim()->toString();
        $sortParams = User::resolveDirectorySort(
            $request->string('sort')->trim()->toString(),
            $request->string('direction')->trim()->toString(),
            'dealer'
        );
        $dealers = User::where('role', 'dealer')
            ->directorySearch($search)
            ->directoryOrder($sortParams['sort'], $sortParams['direction'], 'dealer')
            ->paginate(50)
            ->withQueryString();

        return view('admin.dealers.index', compact('dealers', 'search') + $sortParams);
    }

    public function create()
    {
        return view('admin.dealers.create');
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:100',
            'business_name' => 'required|string|max:255',
        ];

        $validated = $request->validate($rules);

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'phone' => $validated['phone'] ?? null,
            'business_name' => $validated['business_name'],
            'role' => 'dealer',
            'status' => 'active',
        ];

        if (Schema::hasColumn('users', 'ability')) {
            $payload['ability'] = 'fullaccess';
        }

        $user = User::create($payload);
        $user->forceFill(['email_verified_at' => now()])->save();

        return redirect()->route('admin.dealers.index')->with('success', 'Dealer created. They can sign in with the email and password you set.');
    }

    public function approve(User $user)
    {
        if ($user->role !== 'dealer') {
            return back()->with('error', 'User is not a dealer.');
        }

        $user->update(['status' => 'active']);

        app(\App\Services\NotificationDispatchService::class)->dealerApproved($user->fresh());

        return back()->with('success', 'Dealer approved successfully.');
    }
    
    public function reject(User $user)
    {
        if ($user->role !== 'dealer') {
            return back()->with('error', 'User is not a dealer.');
        }

        $user->update(['status' => 'suspended']); // Or delete? Let's suspend for now.

        app(\App\Services\NotificationDispatchService::class)->dealerRejected($user->fresh());

        return back()->with('success', 'Dealer rejected/suspended.');
    }

    public function show(User $user)
    {
        if ($user->role !== 'dealer') {
            abort(404);
        }
        
        $user->load('addresses');

        return view('admin.dealers.show', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        if ($user->role !== 'dealer') {
            abort(404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => 'nullable|string|max:100',
            'business_name' => 'required|string|max:255',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'business_name' => $validated['business_name'],
        ];

        if (! empty($validated['password'])) {
            $payload['password'] = $validated['password'];
        }

        $user->update($payload);

        return redirect()->route('admin.dealers.show', $user)->with('success', 'Dealer information updated.');
    }

    public function destroy(User $user)
    {
        if ($user->role !== 'dealer') {
            abort(404);
        }

        if ((int) $user->id === (int) auth()->id()) {
            return redirect()->route('admin.dealers.index')
                ->withErrors(['error' => 'You cannot delete your own account.']);
        }

        try {
            $user->delete();
        } catch (QueryException $e) {
            return redirect()->route('admin.dealers.index')
                ->withErrors(['error' => 'Cannot delete this dealer because it is linked to existing records.']);
        }

        return redirect()->route('admin.dealers.index')->with('success', 'Dealer deleted successfully.');
    }
}
