<?php

namespace App\Application\GetCharacters;

use App\Domain\Characters\Entities\Character;
use App\Domain\Characters\Contracts\CharacterQueryRepository;

class GetCharacterByIdUseCase
{
    public function __construct(
        private CharacterQueryRepository $repository
    ) {}

    public function execute(int $id): ?Character
    {
        return $this->repository->findById($id);
    }
}
