<?php

namespace App\Infrastructure\Persistence\Mysql;

use App\Domain\Characters\Contracts\CharacterRepositoryInterface;
use App\Domain\Characters\Entities\Character;
use Illuminate\Support\Facades\DB;

class MysqlCharacterRepository implements CharacterRepositoryInterface
{
    private string $table = 'characters';

    public function findAll(): array
    {
        return DB::table($this->table)->get()->map(function ($item) {
            return new Character(
                id: (int) $item->id,
                name: (string) $item->name,
                status: (string) $item->status,
                species: (string) $item->species,
                image: (string) $item->image
            );
        })->all();
    }

    public function save(Character $character): void
    {
        DB::table($this->table)->updateOrInsert(
            ['id' => $character->id],
            [
                'name' => $character->name,
                'status' => $character->status,
                'species' => $character->species,
                'image' => $character->image,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}
