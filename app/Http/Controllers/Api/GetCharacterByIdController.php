<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Application\GetCharacters\GetCharacterByIdUseCase;
use App\Http\Resources\CharacterResource;
use App\Infrastructure\Support\CharacterMeta;
use Illuminate\Http\JsonResponse;

class GetCharacterByIdController extends Controller
{
    public function __construct(
        private GetCharacterByIdUseCase $useCase,
        private CharacterMeta $meta
    ) {}

    public function __invoke(int $id): JsonResponse
    {
        $character = $this->useCase->execute($id);

        if (!$character) {
            return response()->json(['error' => 'Character not found'], 404);
        }

        return response()->json([
            'meta' => [
                'source' => $this->meta->source,
                'host' => $this->meta->host,
                'database' => $this->meta->database,
                'dsn' => $this->meta->dsn,
            ],
            'data' => new CharacterResource($character),
        ]);
    }
}
