<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Ensure sanctum guard is defined (for API token auth from Flutter app)
        $guards = config('auth.guards', []);
        if (! isset($guards['sanctum'])) {
            config(['auth.guards.sanctum' => [
                'driver' => 'sanctum',
                'provider' => 'users',
            ]]);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->applyMailSettingsFromStoreSettings();

        view()->composer('*', function ($view) {
            $cartCount = 0;
            if (\Illuminate\Support\Facades\Auth::check()) {
                $cart = \App\Models\Cart::where('user_id', \Illuminate\Support\Facades\Auth::id())->first();
                if ($cart) {
                    $cartCount = $cart->items()->sum('quantity');
                }
            }
            $view->with('cartCount', $cartCount);
        });
    }

    private function applyMailSettingsFromStoreSettings(): void
    {
        try {
            if (! Schema::hasTable('settings')) {
                return;
            }
        } catch (QueryException $e) {
            return;
        } catch (\Throwable $e) {
            return;
        }

        $settings = Setting::query()
            ->whereIn('key', [
                'mail_mailer',
                'mail_host',
                'mail_port',
                'mail_username',
                'mail_password',
                'mail_encryption',
                'mail_from_address',
                'mail_from_name',
            ])
            ->pluck('value', 'key');

        $mailer = (string) ($settings['mail_mailer'] ?? '');
        $host = (string) ($settings['mail_host'] ?? '');
        $portRaw = $settings['mail_port'] ?? null;
        $username = (string) ($settings['mail_username'] ?? '');
        $password = (string) ($settings['mail_password'] ?? '');
        $encryption = (string) ($settings['mail_encryption'] ?? '');
        $fromAddress = (string) ($settings['mail_from_address'] ?? '');
        $fromName = (string) ($settings['mail_from_name'] ?? '');
        $port = is_numeric($portRaw) ? (int) $portRaw : null;

        if ($mailer !== '') {
            config(['mail.default' => $mailer]);
        }

        if ($host !== '') {
            config(['mail.mailers.smtp.host' => $host]);
        }

        if ($port !== null) {
            config(['mail.mailers.smtp.port' => $port]);
        }

        if ($username !== '') {
            config(['mail.mailers.smtp.username' => $username]);
        }

        if ($password !== '') {
            config(['mail.mailers.smtp.password' => $password]);
        }

        if ($encryption !== '') {
            config(['mail.mailers.smtp.encryption' => $encryption]);
        }

        if ($fromAddress !== '') {
            config(['mail.from.address' => $fromAddress]);
        }

        if ($fromName !== '') {
            config(['mail.from.name' => $fromName]);
        }
    }
}
