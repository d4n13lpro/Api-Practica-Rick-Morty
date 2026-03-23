<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\Characters\Contracts\CharacterRepositoryInterface;
use App\Infrastructure\Persistence\Mongo\MongoCharacterRepository;
use App\Infrastructure\Persistence\Mysql\MysqlCharacterRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // 🔌 Binding dinámico
        $this->app->bind(CharacterRepositoryInterface::class, function ($app) {

            $source = config('database.character_source', 'mongo');

            return match ($source) {
                'mysql' => $app->make(MysqlCharacterRepository::class),
                'mongo' => $app->make(MongoCharacterRepository::class),
                default => throw new \InvalidArgumentException("Invalid DB_SOURCE: {$source}")
            };
        });

        // 🧠 Metadata
        $this->app->singleton('character.meta', function () {

            $source = config('database.character_source', 'mongo');

            $conn = match ($source) {
                'mysql' => config('database.connections.mysql'),
                'mongo' => config('database.connections.mongodb'),
            };

            if (!$conn) {
                throw new \InvalidArgumentException("Invalid DB config for: {$source}");
            }

            return match ($source) {
                'mysql' => [
                    'source'   => 'mysql',
                    'host'     => $conn['host'] ?? 'unknown',
                    'database' => $conn['database'] ?? 'unknown',
                ],

                'mongo' => [
                    'source'   => 'mongo',
                    'dsn'      => $conn['dsn'] ?? 'unknown',
                    'database' => $conn['database'] ?? 'unknown',
                ],
            };
        });
    }
}
