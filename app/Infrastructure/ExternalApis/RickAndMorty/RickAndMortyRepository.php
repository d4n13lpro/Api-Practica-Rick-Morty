<?php

namespace App\Infrastructure\ExternalApis\RickAndMorty;

use App\Domain\Characters\Contracts\CharacterRepositoryInterface;
use App\Domain\Characters\Entities\Character;
use Illuminate\Support\Facades\Http;

class RickAndMortyRepository implements CharacterRepositoryInterface
{
    private string $baseUrl = 'https://rickandmortyapi.com/api';
    public function findAll(): array
    {

        // Añadimos withoutVerifying() para ignorar errores de certificado en local
        $response = Http::withoutVerifying()->get("{$this->baseUrl}/character");
        // Esto detendrá la ejecución y te mostrará en el navegador 
        // lo que la API está devolviendo realmente.
        // dd($response->json());

        if (!$response->successful()) {
            // Si quieres saber qué error da la API, puedes hacer un dd($response->body());
            return [];
        }

        $data = $response->json();

        // Verificamos que 'results' exista antes de mapear
        if (!isset($data['results'])) {
            return [];
        }

        return collect($data['results'])->map(function (array $item) {
            return new Character(
                id: (int) $item['id'],
                name: (string) $item['name'],
                status: (string) $item['status'],
                species: (string) $item['species'],
                image: (string) $item['image']
            );
        })->all();
    }
}
