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
        $this->db
            ->table($this->collection)
            ->updateOrInsert(
                ['id' => $character->id], // 🔑 identidad
                $this->toPersistence($character)
            );
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

    private function toPersistence(Character $character): array
    {
        return [
            'id' => $character->id,
            'name' => $character->name,
            'status' => $character->status,
            'species' => $character->species,
            'image' => $character->image,
            'synced_at' => now(), // ⏱️ técnico (correcto aquí)
        ];
    }
}
