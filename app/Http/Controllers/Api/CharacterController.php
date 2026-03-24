<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Application\GetCharacters\GetCharactersUseCase;
use App\Infrastructure\Support\CharacterMeta;
use App\Http\Resources\CharacterResource;
use Illuminate\Http\JsonResponse;


class CharacterController extends Controller
{
    public function __construct(
        private GetCharactersUseCase $useCase,
        private CharacterMeta $meta
    ) {}

    public function __invoke(): JsonResponse
    {
        try {
            $characters = $this->useCase->execute();

            return response()->json([
                'meta' => $this->buildMeta(),
                'data' => CharacterResource::collection($characters),
            ], 200);
        } catch (\Throwable $e) {

            report($e); // 🔥 importante para logs reales

            return response()->json([
                'meta' => [
                    'source' => $this->meta->source,
                ],
                'error' => [
                    'message' => 'Unexpected error',
                    'details' => config('app.debug') ? $e->getMessage() : null,
                ],
            ], 500);
        }
    }

    private function buildMeta(): array
    {
        return [
            'source'   => $this->meta->source,
            'host'     => $this->meta->host,
            'database' => $this->meta->database,
            'dsn'      => $this->meta->dsn,
        ];
    }
}
