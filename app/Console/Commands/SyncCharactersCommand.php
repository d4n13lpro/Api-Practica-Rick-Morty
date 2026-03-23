<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Infrastructure\ExternalApis\RickAndMorty\RickAndMortyRepository;
use App\Infrastructure\Persistence\Mysql\MysqlCharacterRepository;

class SyncCharactersCommand extends Command
{
    protected $signature = 'characters:sync';
    protected $description = 'Sincroniza personajes desde la API externa hacia MySQL';

    public function handle(
        RickAndMortyRepository $apiRepo,
        MysqlCharacterRepository $mysqlRepo
    ) {
        $this->info('🛰️  Obteniendo datos de la API...');

        $characters = $apiRepo->findAll();

        if (empty($characters)) {
            $this->error('No se encontraron personajes.');
            return;
        }

        // Configuración manual de la barra para control total de tipos
        $bar = $this->output->createProgressBar(count($characters));
        $bar->start();

        foreach ($characters as $character) {
            // $character aquí es una instancia de App\Domain\Characters\Entities\Character
            $mysqlRepo->save($character);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('✅ Sincronización completada: ' . count($characters) . ' personajes en la base de datos.');
    }
}
