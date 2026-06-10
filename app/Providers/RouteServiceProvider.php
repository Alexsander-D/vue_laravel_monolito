<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Caminho padrão após login.
     */
    public const HOME = '/';

    /**
     * Bootstrap das rotas.
     */
    public function boot(): void
    {
        parent::boot();

        $this->routes(function () {

            // Rotas web com prefixo /pos-venda
            Route::middleware('web')->group(function () {
                Route::prefix('/')->group(function () {
                    require base_path('routes/web.php');
                    require base_path('routes/jetstream.php');
                    require base_path('routes/spatie.php');
                    require base_path('routes/fortify.php');
                });
            });

            // Rotas API com prefixo /pos-venda/api
            Route::middleware('api')->group(function () {
                Route::prefix('api')->group(function () {
                    require base_path('routes/api.php');
                });
            });
        });
    }
}
