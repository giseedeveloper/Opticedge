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
        $this->registerActivityLogging();

        view()->composer('*', function ($view) {
            $cartCount = 0;
            $portalPendingCounts = [
                'pending_transfer_requests' => 0,
                'pending_return_requests' => 0,
            ];
            if (\Illuminate\Support\Facades\Auth::check()) {
                $cart = \App\Models\Cart::where('user_id', \Illuminate\Support\Facades\Auth::id())->first();
                if ($cart) {
                    $cartCount = $cart->items()->sum('quantity');
                }
                $portalPendingCounts = \App\Support\PortalPendingRequestCounts::forUser(\Illuminate\Support\Facades\Auth::user());
            }
            $view->with('cartCount', $cartCount);
            $view->with('portalPendingCounts', $portalPendingCounts);
        });
    }

    /**
     * Attach the activity observer to the curated business models and record
     * login / logout events, so the admin System Log captures user activity.
     */
    private function registerActivityLogging(): void
    {
        $models = [
            \App\Models\User::class,
            \App\Models\AgentSale::class,
            \App\Models\AgentCredit::class,
            \App\Models\AgentCreditPayment::class,
            \App\Models\DistributionSale::class,
            \App\Models\DistributionSalePayment::class,
            \App\Models\Purchase::class,
            \App\Models\PurchasePayment::class,
            \App\Models\Product::class,
            \App\Models\Category::class,
            \App\Models\Branch::class,
            \App\Models\Region::class,
            \App\Models\Expense::class,
            \App\Models\PaymentOption::class,
            \App\Models\PaymentTransfer::class,
            \App\Models\Order::class,
            \App\Models\AgentProductTransfer::class,
            \App\Models\RegionalManagerProductTransfer::class,
            \App\Models\TeamLeaderProductTransfer::class,
            \App\Models\AgentDeviceReturn::class,
            \App\Models\RegionalManagerDeviceReturn::class,
            \App\Models\TeamLeaderDeviceReturn::class,
            \App\Models\ContractTerminationRequest::class,
            \App\Models\CustomerNeed::class,
            \App\Models\Setting::class,
            \App\Models\SubadminRole::class,
            \App\Models\Stock::class,
        ];

        foreach ($models as $model) {
            if (class_exists($model)) {
                $model::observe(\App\Observers\ActivityObserver::class);
            }
        }

        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Auth\Events\Login::class,
            function (\Illuminate\Auth\Events\Login $event): void {
                $user = $event->user;
                if (! $user instanceof \App\Models\User) {
                    return;
                }

                \App\Support\ActivityLogger::log(
                    \App\Models\ActivityLog::EVENT_LOGIN,
                    $user->name.' logged in',
                    null,
                    [],
                    $user
                );
            }
        );

        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Auth\Events\Logout::class,
            function (\Illuminate\Auth\Events\Logout $event): void {
                $user = $event->user;
                if (! $user instanceof \App\Models\User) {
                    return;
                }

                \App\Support\ActivityLogger::log(
                    \App\Models\ActivityLog::EVENT_LOGOUT,
                    $user->name.' logged out',
                    null,
                    [],
                    $user
                );
            }
        );
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

        $settingsQuery = Setting::query()->withoutGlobalScopes();

        if (Schema::hasColumn('settings', 'tenant_id')) {
            $settingsQuery->whereNull('tenant_id');
        }

        $settings = $settingsQuery
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
