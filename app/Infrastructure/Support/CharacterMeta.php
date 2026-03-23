<?php

namespace App\Infrastructure\Support;

class CharacterMeta
{
    public function __construct(
        public string $source,
        public string $host,
        public string $port,
        public string $database,
        public string $connection,
    ) {}
}
