<?php

namespace App\Infrastructure\ExternalApis\RickAndMorty;

use App\Domain\Characters\Contracts\CharacterQueryRepository;
use App\Domain\Characters\Entities\Character;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;

class RickAndMortyRepository implements CharacterQueryRepository
{
    // Infra configurable — baseUrl inyectado desde config/services.php via RepositoryServiceProvider.
    public function __construct(
        private HttpFactory $http,
        private string $baseUrl
    ) {}

    public function findAll(): array
    {
        try {
            $response = $this->http
                ->baseUrl($this->baseUrl)
                ->acceptJson()
                ->get('/character')
                ->throw();
        } catch (RequestException $e) {
            throw new \RuntimeException('RickAndMorty API request failed', previous: $e);
        }

        $data = $response->json();

        if (!isset($data['results']) || !is_array($data['results'])) {
            throw new \UnexpectedValueException('Invalid API response structure');
        }

        return collect($data['results'])
            ->map(fn(array $item) => $this->toDomain($item))
            ->all();
    }

    // La API expone un endpoint directo por ID — no necesitamos cargar todos los personajes.
    public function findById(int $id): ?Character
    {
        try {
            $response = $this->http
                ->baseUrl($this->baseUrl)
                ->acceptJson()
                ->get("/character/{$id}")
                ->throw();
        } catch (RequestException $e) {
            // 404 significa que el personaje no existe — retornamos null en lugar de lanzar.
            if ($e->response->status() === 404) {
                return null;
            }
            throw new \RuntimeException('RickAndMorty API request failed', previous: $e);
        }

        return $this->toDomain($response->json());
    }

    // Read-only adapter — save() no aplica en esta fuente.
    public function save(Character $character): void
    {
        throw new \LogicException('RickAndMortyRepository is read-only');
    }

    // Mapper centralizado — convierte el array de la API en una Entidad de dominio.
    private function toDomain(array $item): Character
    {
        return new Character(
            id: (int)    ($item['id']      ?? 0),
            name: (string) ($item['name']    ?? ''),
            status: (string) ($item['status']  ?? ''),
            species: (string) ($item['species'] ?? ''),
            image: (string) ($item['image']   ?? '')
        );
    }
}
