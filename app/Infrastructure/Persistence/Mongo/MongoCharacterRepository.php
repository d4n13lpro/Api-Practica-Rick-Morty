<?php

namespace App\Infrastructure\Persistence\Mongo;

use App\Domain\Characters\Contracts\CharacterRepositoryInterface;
use App\Domain\Characters\Entities\Character;
use Illuminate\Support\Facades\DB;

class MongoCharacterRepository implements CharacterRepositoryInterface
{
    private string $connection = 'mongodb';
    private string $collection = 'characters';

    public function findAll(): array
    {
        // ELIMINAMOS ->toArray() de aquí para mantenerlo como Colección y poder usar map()
        $documents = DB::connection($this->connection)
            ->table($this->collection)
            ->get();

        return $documents->map(function ($doc) {
            // Ahora $doc es un objeto stdClass, por eso usamos ->
            return new Character(
                id: (int) $doc->id,
                name: (string) $doc->name,
                status: (string) $doc->status,
                species: (string) $doc->species,
                image: (string) $doc->image
            );
        })->all(); // .all() convierte la colección final en el array que pide el contrato
    }

    public function save(Character $character): void
    {
        DB::connection($this->connection)
            ->table($this->collection)
            ->updateOrInsert(
                ['id' => $character->id],
                [
                    'name' => $character->name,
                    'status' => $character->status,
                    'species' => $character->species,
                    'image' => $character->image,
                    'synced_at' => now(),
                ]
            );
    }
}
