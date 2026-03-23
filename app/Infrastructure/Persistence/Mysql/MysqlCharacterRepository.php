<?php

namespace App\Infrastructure\Persistence\Mysql;


use App\Domain\Characters\Entities\Character;
use Illuminate\Database\Connection;
use App\Domain\Characters\Contracts\CharacterQueryRepository;
use App\Domain\Characters\Contracts\CharacterCommandRepository;

class MysqlCharacterRepository implements CharacterQueryRepository, CharacterCommandRepository
{
    private string $table = 'characters';

    public function __construct(
        private Connection $db // ✅ Inyección → desacoplado y testeable
    ) {}

    public function findAll(): array
    {
        // 🔄 Infra → Domain (via Mapper)
        $rows = $this->db
            ->table($this->table)
            ->get();

        return $rows
            ->map(fn($row) => $this->toDomain($row))
            ->all();
    }

    public function save(Character $character): void
    {
        // 🔄 Domain → Infra (via Mapper)
        $this->db
            ->table($this->table)
            ->updateOrInsert(
                ['id' => $character->id], // 🔑 identidad
                $this->toPersistence($character)
            );
    }

    // =========================
    // 🔁 MAPPER (centralizado)
    // =========================

    private function toDomain(object $row): Character
    {
        return new Character(
            id: (int) ($row->id ?? 0),
            name: (string) ($row->name ?? ''),
            status: (string) ($row->status ?? ''),
            species: (string) ($row->species ?? ''),
            image: (string) ($row->image ?? '')
        );
    }

    private function toPersistence(Character $character): array
    {
        $now = now();

        return [
            'id' => $character->id,
            'name' => $character->name,
            'status' => $character->status,
            'species' => $character->species,
            'image' => $character->image,

            // ⏱️ Manejo correcto de timestamps
            // ⚠️ updateOrInsert no distingue create/update → esto es workaround
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
}
