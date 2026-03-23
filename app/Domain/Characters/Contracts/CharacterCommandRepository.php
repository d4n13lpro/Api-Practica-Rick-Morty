<?php

namespace App\Domain\Characters\Contracts;

use App\Domain\Characters\Entities\Character;

interface CharacterCommandRepository
{
    public function save(Character $character): void;
}
