<?php

namespace App\Providers;
use Illuminate\Support\Facades\URL;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Routing\Router;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public function map()
{
    $this->mapApiRoutes();
    $this->mapWebRoutes();
}

protected function mapApiRoutes()
{
    Route::prefix('api') // Убедитесь, что префикс установлен на 'api'
        ->middleware('api') // Middleware для API
        ->namespace($this->namespace) // Namespace для контроллеров
        ->group(base_path('routes/api.php'));       
}

protected function mapWebRoutes()
{
    Route::middleware('web')
        ->namespace($this->namespace)
        ->group(base_path('routes/web.php'));
}

public function boot(): void
    {
            // Принудительно устанавливаем схему HTTPS
    if ($this->app->environment('production')) {
        URL::forceScheme('https');
    }
        // Регистрация кастомного middleware
        $this->app['router']->aliasMiddleware('role', RoleMiddleware::class);

        // Вызывайте родительский boot() для маршрутов, если нужно
        parent::boot();
    }
}
