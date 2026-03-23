<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\DatabaseManager;
use App\Infrastructure\Persistence\Mongo\MongoCharacterRepository;
use App\Infrastructure\Persistence\Mysql\MysqlCharacterRepository;
use App\Infrastructure\Support\CharacterMeta;
use App\Domain\Characters\Contracts\CharacterQueryRepository;
use App\Domain\Characters\Contracts\CharacterCommandRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // 🔥 1. Crear UNA sola instancia del repo (shared)
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

        // 🔥 2. Resolver dinámicamente el repo activo
        $this->app->singleton('character.repo', function ($app) {

            $source = config('database.character_source', 'mongo');

            return match ($source) {
                'mysql' => $app->make(MysqlCharacterRepository::class),
                'mongo' => $app->make(MongoCharacterRepository::class),
                default => throw new \InvalidArgumentException("Invalid DB_SOURCE: {$source}")
            };
        });

        // 🔥 3. Bind de interfaces → MISMA instancia
        $this->app->bind(CharacterQueryRepository::class, function ($app) {
            return $app->make('character.repo');
        });

        $this->app->bind(CharacterCommandRepository::class, function ($app) {
            return $app->make('character.repo');
        });

        // 🧠 Metadata (esto está bien, solo pequeño ajuste)
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
                    host: $conn['host'] ?? 'unknown',
                    database: $conn['database'] ?? 'unknown',
                ),

                'mongo' => new CharacterMeta(
                    source: 'mongo',
                    database: $conn['database'] ?? 'unknown',
                    dsn: $conn['dsn'] ?? null,
                ),
            };
        });
    }
}
