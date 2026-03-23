<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
// IMPORTANTE: Verifica que estas rutas (namespaces) coincidan con tus carpetas
use App\Domain\Characters\Contracts\CharacterRepositoryInterface;
use App\Infrastructure\ExternalApis\RickAndMorty\RickAndMortyRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Registra los enlaces del repositorio.
     */
    public function register(): void
    {
        $this->app->bind(
            CharacterRepositoryInterface::class,
            RickAndMortyRepository::class
        );
    }

    public function boot(): void
    {
        //
    }
}
