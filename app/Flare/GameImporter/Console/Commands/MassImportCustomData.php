<?php

namespace App\Flare\GameImporter\Console\Commands;

use App\Flare\Models\Event;
use App\Flare\Models\FactionLoyalty;
use App\Flare\Models\FactionLoyaltyNpc;
use App\Flare\Models\GameMap;
use App\Flare\Models\GlobalEventGoal;
use App\Flare\Models\GlobalEventKill;
use App\Flare\Models\GlobalEventParticipation;
use App\Flare\Models\InfoPage;
use App\Flare\Values\MapNameValue;
use App\Game\Events\Values\EventType;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Http\File;
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

        $correctOrder = [
            "Twisted Memories.jpeg",
            "Delusional Memories.jpeg",
        ];

        $this->importGameMaps($correctOrder);

        Artisan::call('import:game-data Items');
        Artisan::call('import:game-data Monsters');
        Artisan::call('import:game-data Locations');
        Artisan::call('import:game-data Npcs');

        //Clean up previous events
        GlobalEventGoal::truncate();
        GlobalEventKill::truncate();
        GlobalEventParticipation::truncate();

        $factionLoyaties = FactionLoyalty::where('faction_id', GameMap::where('name', MapNameValue::ICE_PLANE)->first()->id)->get();

        foreach ($factionLoyaties as $factionLoyaty) {
            $factionLoyaty->factionLoyaltyNpcs()->get()->each(function ($npc) {
                FactionLoyaltyNpc::where('id', $npc->id)->update([
                    'currently_helping' => false
                ]);
            });
        }

        FactionLoyalty::where('faction_id', GameMap::where('name', MapNameValue::ICE_PLANE)->first()->id)->update([
            'is_pledged' => false,
        ]);

        Artisan::call('import:game-data Quests');

        // Due to the way quests are ordered, and their dependencies on other quests we have to double import to make
        // sure all relationships are properly setup.
        Artisan::call('import:game-data Quests');

        Artisan::call('import:game-data "Admin Section"');

        Artisan::call('change:feature-types-on-quests');
        Artisan::call('give:new-slots-quest-item');
        Artisan::call('allow:traverse-for-maps');

        Artisan::call('balance:monsters');
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

    protected function importGameMaps(array $orderedMapImages): void {
        $files = Storage::disk('data-maps')->allFiles();

        // Sort the array such that the maps are in the correct order.
        usort($files, function ($a, $b) use ($orderedMapImages) {
            $indexA = array_search($a, $orderedMapImages);
            $indexB = array_search($b, $orderedMapImages);

            return $indexA - $indexB;
        });

        foreach ($files as $file) {

            if (in_array($file, $orderedMapImages)) {
                $fileName = pathinfo($file, PATHINFO_FILENAME);

                $path     = Storage::disk('maps')->putFile($fileName, new File(resource_path('maps') . '/' . $file));

                $mapValue = new MapNameValue($fileName);

                $gameMapData = array_merge([
                    'name'          => $fileName,
                    'path'          => $path,
                    'default'       => $mapValue->isSurface(),
                    'kingdom_color' => MapNameValue::$kingdomColors[$fileName],
                ], (new MapNameValue($fileName))->getMapModifers());

                GameMap::create($gameMapData);
            }
        }
    }
}
