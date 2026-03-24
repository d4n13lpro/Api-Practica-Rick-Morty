<?php

namespace App\Infrastructure\Persistence\Mongo;

use App\Domain\Characters\Entities\Character;
use Illuminate\Database\Connection;
use App\Domain\Characters\Contracts\CharacterQueryRepository;
use App\Domain\Characters\Contracts\CharacterCommandRepository;

class MongoCharacterRepository implements
    CharacterQueryRepository,
    CharacterCommandRepository
{
    // 🔌 Infra pura (configurable, no hardcodeado a Facade)
    private string $collection = 'characters';

    public function __construct(
        private Connection $db // ✅ Inyección → testeable
    ) {}

    public function findAll(): array
    {
        // 🔄 Infra → Domain (via Mapper)
        $documents = $this->db
            ->table($this->collection)
            ->get();

        return $documents
            ->map(fn($doc) => $this->toDomain($doc))
            ->all();
    }

    public function findById(int $id): ?Character
    {
        $doc = $this->db->table($this->collection)->where('id', $id)->first();
        return $doc ? $this->toDomain($doc) : null;
    }

    public function save(Character $character): void
    {
        // 🔄 Domain → Infra (via Mapper)
        $exists = $this->db
            ->table($this->collection)
            ->where('id', $character->id)
            ->exists();

        if ($exists) {
            // ✅ Solo actualiza campos de datos + synced_at
            $this->db
                ->table($this->collection)
                ->where('id', $character->id)
                ->update($this->toUpdate($character));
        } else {
            // ✅ Inserta con created_at y synced_at
            $this->db
                ->table($this->collection)
                ->insert($this->toInsert($character));
        }
    }

    // =========================
    // 🔁 MAPPER (centralizado)
    // =========================

    private function toDomain(object $doc): Character
    {
        // 🛡️ Protección básica ante cambios de schema
        return new Character(
            id: (int) ($doc->id ?? 0),
            name: (string) ($doc->name ?? ''),
            status: (string) ($doc->status ?? ''),
            species: (string) ($doc->species ?? ''),
            image: (string) ($doc->image ?? '')
        );
    }

    private function toInsert(Character $character): array
    {
        return [
            'id'         => $character->id,
            'name'       => $character->name,
            'status'     => $character->status,
            'species'    => $character->species,
            'image'      => $character->image,
            'created_at' => now(), // ✅ solo al crear
            'synced_at'  => now(), // ⏱️ técnico
        ];
    }

    private function toUpdate(Character $character): array
    {
        return [
            'name'      => $character->name,
            'status'    => $character->status,
            'species'   => $character->species,
            'image'     => $character->image,
            'synced_at' => now(), // ✅ se actualiza en cada sync
        ];
    }
}
