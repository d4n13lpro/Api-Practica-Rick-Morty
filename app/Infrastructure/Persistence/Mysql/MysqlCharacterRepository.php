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

    public function findById(int $id): ?Character
    {
        $row = $this->db->table($this->table)->find($id);
        return $row ? $this->toDomain($row) : null;
    }

    public function save(Character $character): void
    {
        // 🔄 Domain → Infra (via Mapper)
        $exists = $this->db
            ->table($this->table)
            ->where('id', $character->id)
            ->exists();

        if ($exists) {
            // ✅ Solo actualiza campos de datos + updated_at
            $this->db
                ->table($this->table)
                ->where('id', $character->id)
                ->update($this->toUpdate($character));
        } else {
            // ✅ Inserta con created_at y updated_at
            $this->db
                ->table($this->table)
                ->insert($this->toInsert($character));
        }
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

    private function toInsert(Character $character): array
    {
        return [
            'id'         => $character->id,
            'name'       => $character->name,
            'status'     => $character->status,
            'species'    => $character->species,
            'image'      => $character->image,
            'created_at' => now(), // ✅ solo al crear
            'updated_at' => now(),
        ];
    }

    private function toUpdate(Character $character): array
    {
        return [
            'name'       => $character->name,
            'status'     => $character->status,
            'species'    => $character->species,
            'image'      => $character->image,
            'updated_at' => now(), // ✅ solo updated_at al actualizar
        ];
    }
}
