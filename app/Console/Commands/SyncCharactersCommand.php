<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Infrastructure\ExternalApis\RickAndMorty\RickAndMortyRepository;
use App\Domain\Characters\Contracts\CharacterCommandRepository;

class SyncCharactersCommand extends Command
{
    protected $signature = 'characters:sync';
    protected $description = 'Sincroniza personajes desde la API externa hacia el motor de persistencia configurado';

    public function handle(
        RickAndMortyRepository $apiRepo,
        CharacterCommandRepository $persistenceRepo
    ): void {
        $this->info('🛰️  Obteniendo datos de la API...');

        try {
            $characters = $apiRepo->findAll();

            if (empty($characters)) {
                $this->warn('No se encontraron personajes.');
                return;
            }

            $bar = $this->output->createProgressBar(count($characters));
            $bar->start();

            foreach ($characters as $character) {
                $persistenceRepo->save($character);
                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            $this->info('✅ Sincronización completada: ' . count($characters) . ' personajes procesados.');
        } catch (\Throwable $e) {

            report($e); // 🔥 importante para producción

            $this->error('❌ Error durante la sincronización.');

            if (config('app.debug')) {
                $this->line($e->getMessage());
            }
        }
    }
}
