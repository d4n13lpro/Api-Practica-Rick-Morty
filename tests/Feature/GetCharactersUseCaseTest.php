<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Application\GetCharacters\GetCharactersUseCase;
use App\Domain\Characters\Contracts\CharacterQueryRepository;
use App\Domain\Characters\Entities\Character;

class GetCharactersUseCaseTest extends TestCase
{
    public function test_it_returns_characters_from_repository(): void
    {
        // 🧠 Arrange (preparar)
        $fakeCharacters = [
            new Character(1, 'Rick', 'Alive', 'Human', 'img1'),
            new Character(2, 'Morty', 'Alive', 'Human', 'img2'),
        ];

        // 🔥 Mock del repositorio (clave)
        $mockRepo = $this->createMock(CharacterQueryRepository::class);

        $mockRepo->expects($this->once())
            ->method('findAll')
            ->willReturn($fakeCharacters);

        // ⚙️ Use case
        $useCase = new GetCharactersUseCase($mockRepo);

        // 🚀 Act (ejecutar)
        $result = $useCase->execute();

        // ✅ Assert (validar)
        $this->assertCount(2, $result);
        $this->assertEquals('Rick', $result[0]->name);
    }
}
