<?php

namespace App\Infrastructure\Support;

/**
 * Metadata de la conexión activa.
 * Expuesta en la respuesta HTTP para trazabilidad de la fuente de datos en uso.
 */

class CharacterMeta
{
    public function __construct(
        public string $source,
        public string $host = 'unknown',
        public string $database = 'unknown',
        public ?string $dsn = null,
    ) {}
}
