<?php

namespace App\Domain\Characters\Contracts;

use App\Domain\Characters\Entities\Character;

interface CharacterRepositoryInterface
{
    public function findAll(): array;

    /**
     * Ahora PHP ya sabe que Character se refiere a la entidad del dominio
     */
    public function save(Character $character): void;
}
