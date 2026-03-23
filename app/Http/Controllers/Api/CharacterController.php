<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Application\GetCharacters\GetCharactersUseCase;
use Illuminate\Http\JsonResponse;

class CharacterController extends Controller
{
    public function __construct(
        private GetCharactersUseCase $useCase
    ) {}

    public function __invoke(): JsonResponse
    {
        $meta = app('character.meta'); // 👈 correcto

        return response()->json([
            'meta'    => $meta,
            'success' => true,
            'data'    => $this->useCase->execute(),
        ]);
    }
}
