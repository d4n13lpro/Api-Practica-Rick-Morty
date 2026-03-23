<?php

namespace App\Application\GetCharacters;

use App\Domain\Characters\Contracts\CharacterQueryRepository;
use App\Domain\Characters\Contracts\CharacterRepositoryInterface;


class GetCharactersUseCase
{
    /**
     * IMPORTANTE: Inyectamos la INTERFAZ (el puerto), no la clase de RickAndMorty.
     * Esto hace que nuestro caso de uso sea agnóstico a la fuente de datos.
     */
    public function __construct(
        private CharacterQueryRepository $repository
    ) {}

    /**
     * Ejecuta la lógica del caso de uso.
     * * @return \App\Domain\Characters\Entities\Character[]
     */
    public function execute(): array
    {
        // Aquí podrías agregar lógica extra, por ejemplo:
        // - Validar si el usuario tiene permisos.
        // - Guardar un log de la consulta.
        // - Transformar los datos si fuera necesario.

        return $this->repository->findAll();
    }
}
