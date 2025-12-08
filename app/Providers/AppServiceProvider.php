<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Administration;
use App\Observers\AdministrationObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force les réponses JSON pour toutes les requêtes API
        if (request()->is('api/*')) {
            request()->headers->set('Accept', 'application/json');
        }

        // Enregistrer l'observer pour la génération automatique des clés
        Administration::observe(AdministrationObserver::class);
    }
}
