<?php

namespace App\Flare\GameImporter\Console\Commands;

use App\Flare\Models\GameMap;
use App\Flare\Models\InfoPage;
use App\Game\Events\Values\EventType;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

class MassImportCustomData extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mass:import-game-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports Game Data in a specific way defined by the programmer';

    /**
     * Execute the console command.
     */
    public function handle() {

        Artisan::call('reduce:unit-queue-amount');
        Artisan::call('remove:invalid-quest-items');

        Artisan::call('import:game-data "Core Imports"');
        Artisan::call('import:game-data Skills');
        Artisan::call('import:game-data Items');
        Artisan::call('import:game-data "Kingdom Passive Skills"');
        Artisan::call('import:game-data "Admin Section"');
        Artisan::call('import:game-data "Quests"');
        Artisan::call('import:game-data "."');

        Artisan::call('add:holy-stacks-to-items');
        Artisan::call('assign:new-skills');
        Artisan::call('create:character-attack-data');
        Artisan::call('generate:monster-cache');
        Artisan::call('create:quest-cache');


        $this->importInformationSection();
    }

    protected function importInformationSection(): void {
        $data = Storage::disk('data-imports')->get('Admin Section/information.json');

        $data = json_decode(trim($data), true);

        foreach ($data as $modelEntry) {
            InfoPage::updateOrCreate(['id' => $modelEntry['id']], $modelEntry);
        }

        $sourceDirectory      = resource_path('backup/info-sections-images');
        $destinationDirectory = storage_path('app/public');

        $command = 'cp -R ' . escapeshellarg($sourceDirectory) . ' ' . escapeshellarg($destinationDirectory);
        exec($command, $output, $exitCode);

        if ($exitCode === 0) {
            $this->line('Information section images directory copied to public successfully. Information section is now set up.');
        } else {
            $this->line('Failed to copy the information images directory over. You can do this manually from the resources/backup/information-sections-images. Copy the entire directory to app/public');
        }
    }
}
