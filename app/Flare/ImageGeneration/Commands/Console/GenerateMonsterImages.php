<?php

namespace App\Flare\ImageGeneration\Commands\Console;

use App\Flare\ImageGeneration\Services\DeepAiImageTextGenerationService;
use App\Flare\Models\GameMap;
use App\Flare\Models\Monster;
use Illuminate\Console\Command;

class GenerateMonsterImages extends Command
{
    protected $signature = 'generate:monster-images {gameMapName}';
    protected $description = 'Generates an image based on a prompt';

    public function handle(DeepAiImageTextGenerationService $deepAiImageTextGenerationService) {
        $forGameMap = $this->argument('gameMapName');

        if (empty($forGameMap)) {
            $this->error('Missing game Map Name');
            return;
        }

        $apiKey = env('DEEP_AI_API_KEY');

        if (empty($apiKey)) {
            $this->error('Missing APi Key for Deep AI');
            return;
        }

        $gameMap = GameMap::where('name', $forGameMap)->first();

        if (is_null($gameMap)) {
            $this->error('Unknown game map for name: ' . $forGameMap);
            return;
        }

        $monsters = Monster::where('game_map_id', $gameMap->id)->get();
        $bar = $this->output->createProgressBar($monsters->count());
        $bar->start();

        foreach ($monsters as $monster) {

            $pathForImage = '/' . $gameMap->name . '/' . $monster->name . '.jpg';

            if ($deepAiImageTextGenerationService->imageAlreadyGeneratedForMonster($pathForImage)) {

                $bar->advance();
                $this->line(' Skipping, monster has image.');

                continue;
            }

            $response = $deepAiImageTextGenerationService->setApiKey($apiKey)->generateImage('Generate an Epic High Fantasy: ' . $monster->name . ' without any additional text, just the image.');

            if ($response) {
                $outputUrl = $response['output_url'];
                $saved = $deepAiImageTextGenerationService->downloadAndSaveImage($outputUrl, $pathForImage);

                if ($saved) {
                    $this->line(' Saved image for monster: ' . $monster->name . ' who belongs to game map: ' . $gameMap->name);
                } else {
                    $this->error('Something went wrong saving the image');
                }
            } else {
                $this->error('Something went wrong saving the image');
            }

            $bar->advance();
            sleep(2);
        }

        $bar->finish();
        $this->info("\nImage generation completed.");
    }
}
