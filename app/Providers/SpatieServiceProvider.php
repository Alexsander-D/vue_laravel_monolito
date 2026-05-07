<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class SpatieServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Aqui você pode registrar serviços específicos do Spatie se necessário.
        // Por exemplo, você pode registrar implementações de interfaces ou serviços personalizados.
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configure as rotas específicas do Spatie
        $this->configureRoutes();
        
        // Você pode adicionar outras configurações específicas aqui, se necessário
    }

    /**
     * Configure as rotas específicas do Spatie.
     *
     * @return void
     */
    protected function configureRoutes()
    {
        Route::group([
            'middleware' => ['web', 'auth'], // Adicione os middlewares necessários
            'namespace' => 'App\Http\Controllers\Spatie',
        ], function () {
            $this->loadRoutesFrom(base_path('/routes/spatie.php'));
        });
    }
}
