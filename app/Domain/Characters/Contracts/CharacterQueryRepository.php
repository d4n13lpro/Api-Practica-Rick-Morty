<?php

namespace App\Domain\Characters\Contracts;

use App\Domain\Characters\Entities\Character;

interface CharacterQueryRepository
{
    /**
     * @return Character[]
     */
    public function findAll(): array;
}
