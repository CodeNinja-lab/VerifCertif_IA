<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Administration;
use App\Models\ProfilCompetence;
use App\Models\Certification;
use App\Models\Offre;
use App\Models\OffreCompetence;
use App\Observers\AdministrationObserver;
use App\Observers\ProfilCompetenceObserver;
use App\Observers\CertificationObserver;
use App\Observers\OffreObserver;
use App\Observers\OffreCompetenceObserver;

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

        // Enregistrer les observers
        Administration::observe(AdministrationObserver::class);
        ProfilCompetence::observe(ProfilCompetenceObserver::class);
        Certification::observe(CertificationObserver::class);
        Offre::observe(OffreObserver::class);
        OffreCompetence::observe(OffreCompetenceObserver::class);
    }
}
