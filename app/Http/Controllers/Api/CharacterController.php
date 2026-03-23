<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Application\GetCharacters\GetCharactersUseCase;
use Illuminate\Http\JsonResponse;

class CharacterController extends Controller
{
    // Fíjate que ponemos "private" dentro del paréntesis
    public function __construct(
        private GetCharactersUseCase $getCharactersUseCase
    ) {}

    public function __invoke(): JsonResponse
    {
        // Ahora sí estará inicializada automáticamente por Laravel
        $characters = $this->getCharactersUseCase->execute();

        return response()->json([
            'success' => true,
            'data'    => $characters,
        ]);
    }
}
