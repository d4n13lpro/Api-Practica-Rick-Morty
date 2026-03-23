<?php

namespace App\Infrastructure\Support;

class CharacterMeta
{
    public function __construct(
        public string $source,
        public string $host = 'unknown',
        public string $database = 'unknown',
        public ?string $dsn = null,
    ) {}
}
