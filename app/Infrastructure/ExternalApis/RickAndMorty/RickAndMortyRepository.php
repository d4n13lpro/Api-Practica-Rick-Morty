<?php

namespace App\Infrastructure\ExternalApis\RickAndMorty;

use App\Domain\Characters\Contracts\CharacterRepositoryInterface;
use App\Domain\Characters\Entities\Character;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;

class RickAndMortyRepository implements CharacterRepositoryInterface
{
    // 🔌 Infra configurable (desacoplado de hardcode)
    public function __construct(
        private HttpFactory $http,
        private string $baseUrl // ← inyectar desde config/services.php
    ) {}

    public function findAll(): array
    {
        try {
            // 🌐 Cliente HTTP limpio (sin hacks inseguros)
            $response = $this->http
                ->baseUrl($this->baseUrl)
                ->acceptJson()
                ->get('/character')
                ->throw(); // 🛑 lanza excepción si falla (fail fast)

        } catch (RequestException $e) {
            // ❌ Infra error explícito (no silencioso)
            throw new \RuntimeException(
                'RickAndMorty API request failed',
                previous: $e
            );
        }

        $data = $response->json();

        // 🛡️ Validación del contrato externo
        if (!isset($data['results']) || !is_array($data['results'])) {
            throw new \UnexpectedValueException('Invalid API response structure');
        }

        // 🔄 Infra → Domain (mapper centralizado)
        return collect($data['results'])
            ->map(fn(array $item) => $this->toDomain($item))
            ->all();
    }

    public function save(Character $character): void
    {
        // 🚫 Read-only adapter → comportamiento explícito
        throw new \LogicException('RickAndMortyRepository is read-only');
    }

    // =========================
    // 🔁 MAPPER (centralizado)
    // =========================

    private function toDomain(array $item): Character
    {
        return new Character(
            id: (int) ($item['id'] ?? 0),
            name: (string) ($item['name'] ?? ''),
            status: (string) ($item['status'] ?? ''),
            species: (string) ($item['species'] ?? ''),
            image: (string) ($item['image'] ?? '')
        );
    }
}
