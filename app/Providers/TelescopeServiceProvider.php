<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Telescope::night();

        $this->hideSensitiveRequestDetails();
        Telescope::filter(function (IncomingEntry $entry) {
        return true; // Записываем ВСЕ события
    });

    }

    /**
     * Prevent sensitive request details from being logged by Telescope.
     */
    protected function hideSensitiveRequestDetails(): void
    {
        if ($this->app->environment('local')) {
            return;
        }

        Telescope::hideRequestParameters(['_token']);

        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }

    /**
     * Register the Telescope gate.
     *
     * This gate determines who can access Telescope in non-local environments.
     */
    protected function gate(): void
    {
    Gate::define('viewTelescope', function ($user = null) {
        $allowedIps = ['178.214.247.23']; // Замени на свой реальный IP

        // Получаем IP из заголовков (если есть прокси)
        $requestIp = request()->ip();
        $forwardedIp = request()->header('X-Forwarded-For');

        // Разрешаем доступ, если IP в списке
        return in_array($requestIp, $allowedIps) || in_array($forwardedIp, $allowedIps);
    });
    }
}
