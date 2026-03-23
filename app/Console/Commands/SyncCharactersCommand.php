<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Infrastructure\ExternalApis\RickAndMorty\RickAndMortyRepository;
// Mantener estos imports para referencia
use App\Infrastructure\Persistence\Mysql\MysqlCharacterRepository;
use App\Domain\Characters\Contracts\CharacterRepositoryInterface;

class SyncCharactersCommand extends Command
{
    protected $signature = 'characters:sync';
    protected $description = 'Sincroniza personajes desde la API externa hacia el motor de persistencia configurado';

    public function handle(
        RickAndMortyRepository $apiRepo,
        // MysqlCharacterRepository $mysqlRepo // COMENTADO: Referencia anterior a MySQL
        CharacterRepositoryInterface $persistenceRepo // ADICIONADO: Inyectamos la Interfaz (Puerto)
    ) {
        $this->info('🛰️  Obteniendo datos de la API...');

        $characters = $apiRepo->findAll();

        if (empty($characters)) {
            $this->error('No se encontraron personajes.');
            return;
        }

        $bar = $this->output->createProgressBar(count($characters));
        $bar->start();

        foreach ($characters as $character) {
            // $mysqlRepo->save($character); // COMENTADO: Lógica antigua de MySQL

            // Esta línea ahora guarda en MongoDB porque el Provider 
            // vinculó la Interfaz con MongoCharacterRepository
            $persistenceRepo->save($character);

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('✅ Sincronización completada: ' . count($characters) . ' personajes procesados.');
    }
}
