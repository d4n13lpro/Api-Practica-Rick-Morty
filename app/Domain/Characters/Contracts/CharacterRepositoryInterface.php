<?php

namespace App\Domain\Characters\Contracts;

interface CharacterRepositoryInterface
{
    public function findAll(): array;
}
