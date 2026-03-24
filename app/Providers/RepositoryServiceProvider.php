<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\DatabaseManager;
use App\Infrastructure\Persistence\Mongo\MongoCharacterRepository;
use App\Infrastructure\Persistence\Mysql\MysqlCharacterRepository;
use App\Infrastructure\Support\CharacterMeta;
use App\Domain\Characters\Contracts\CharacterQueryRepository;
use App\Domain\Characters\Contracts\CharacterCommandRepository;
use App\Infrastructure\ExternalApis\RickAndMorty\RickAndMortyRepository;
use Illuminate\Http\Client\Factory as HttpFactory;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Adaptador de la API externa — recibe el cliente HTTP y la URL base desde config.
        $this->app->singleton(RickAndMortyRepository::class, function ($app) {
            return new RickAndMortyRepository(
                $app->make(HttpFactory::class),
                config('services.rickandmorty.base_url')
            );
        });

        // Adaptadores de persistencia — singleton garantiza una sola conexión por request.
        $this->app->singleton(MongoCharacterRepository::class, function ($app) {
            /** @var DatabaseManager $db */
            $db = $app->make('db');
            return new MongoCharacterRepository($db->connection('mongodb'));
        });

        $this->app->singleton(MysqlCharacterRepository::class, function ($app) {
            /** @var DatabaseManager $db */
            $db = $app->make('db');
            return new MysqlCharacterRepository($db->connection('mysql'));
        });

        // Resuelve el repositorio activo según DB_SOURCE en .env (mysql | mongo).
        // Registrado como alias 'character.repo' para ser compartido entre las interfaces CQRS.
        $this->app->singleton('character.repo', function ($app) {
            $source = config('database.character_source', 'mongo');

            return match ($source) {
                'mysql' => $app->make(MysqlCharacterRepository::class),
                'mongo' => $app->make(MongoCharacterRepository::class),
                default => throw new \InvalidArgumentException("Invalid DB_SOURCE: {$source}")
            };
        });

        // Ambas interfaces CQRS apuntan a la misma instancia del repositorio activo.
        $this->app->bind(CharacterQueryRepository::class, fn($app) => $app->make('character.repo'));
        $this->app->bind(CharacterCommandRepository::class, fn($app) => $app->make('character.repo'));

        // Construye los metadatos de conexión según la fuente activa.
        // Usado por CharacterController para exponer info de infraestructura en la respuesta.
        $this->app->singleton(CharacterMeta::class, function () {
            $source = config('database.character_source', 'mongo');

            $conn = match ($source) {
                'mysql' => config('database.connections.mysql'),
                'mongo' => config('database.connections.mongodb'),
                default => null,
            };

            if (!$conn) {
                throw new \InvalidArgumentException("Invalid DB config for: {$source}");
            }

            return match ($source) {
                'mysql' => new CharacterMeta(
                    source: 'mysql',
                    host: $conn['host']     ?? 'unknown',
                    database: $conn['database'] ?? 'unknown',
                ),
                'mongo' => new CharacterMeta(
                    source: 'mongo',
                    database: $conn['database'] ?? 'unknown',
                    dsn: $conn['dsn']      ?? null,
                ),
            };
        });
    }
}
