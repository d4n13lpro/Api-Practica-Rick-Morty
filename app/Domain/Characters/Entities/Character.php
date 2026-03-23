<?php

namespace App\Domain\Characters\Entities;

readonly class Character
{
    public function __construct(
        public int $id,
        public string $name,
        public string $status,
        public string $species,
        public string $image,

    ) {}
}
