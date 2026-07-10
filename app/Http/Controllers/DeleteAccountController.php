<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class DeleteAccountController extends Controller
{
    public function show(): View
    {
        return view('delete-account');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $to = config('mail.from.address', 'support@opticedgeafrica.net');
        $body = implode("\n", [
            'Account deletion request received from the public delete-account page.',
            '',
            'Name: '.$validated['name'],
            'Email: '.$validated['email'],
            'Phone: '.($validated['phone'] ?: '—'),
            'Reason: '.($validated['reason'] ?: '—'),
            '',
            'Submitted at: '.now()->toDateTimeString(),
            'IP: '.$request->ip(),
        ]);

        try {
            Mail::raw($body, function ($message) use ($to, $validated) {
                $message
                    ->to($to)
                    ->replyTo($validated['email'], $validated['name'])
                    ->subject('Account deletion request — '.$validated['email']);
            });
        } catch (\Throwable $e) {
            Log::error('Failed to send account deletion request email', [
                'email' => $validated['email'],
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->withErrors([
                    'email' => 'We could not submit your request right now. Please email support@opticedgeafrica.net directly.',
                ]);
        }

        return redirect()
            ->route('delete-account')
            ->with('status', 'Your account deletion request has been submitted. Our team will process it within 7 days and contact you at the email you provided.');
    }
}
