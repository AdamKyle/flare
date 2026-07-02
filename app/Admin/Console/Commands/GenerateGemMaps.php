<?php

namespace App\Admin\Console\Commands;

use App\Flare\GemWorldGeneration\Services\GemWorldGenerationService;
use App\Flare\Models\GameLocationGemParamter;
use App\Flare\Models\GameMapGemParamter;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class GenerateGemMaps extends Command
{
    private const GENERATION_CHOICES = [
        'Map Gem',
        'Location Gem',
        'All',
    ];

    protected $signature = 'generate:gem-maps';

    protected $description = 'Generate hidden gem world maps for map and location gem profiles.';

    public function __construct(
        private readonly GemWorldGenerationService $gemWorldGenerationService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $choice = $this->askForChoice('What do you want to generate?', self::GENERATION_CHOICES);

        if ($choice === 'Map Gem') {
            $summary = $this->generateMapGemParamters($this->chooseMapGemParamters());
            $this->showSummary($summary);

            return self::SUCCESS;
        }

        if ($choice === 'Location Gem') {
            $summary = $this->generateLocationGemParamters($this->chooseLocationGemParamters());
            $this->showSummary($summary);

            return self::SUCCESS;
        }

        $mapSummary = $this->generateMapGemParamters($this->mapGemParamters());
        $locationSummary = $this->generateLocationGemParamters($this->locationGemParamters());

        $this->showSummary([
            'created' => $mapSummary['created'] + $locationSummary['created'],
            'skipped' => $mapSummary['skipped'] + $locationSummary['skipped'],
        ]);

        return self::SUCCESS;
    }

    private function chooseMapGemParamters(): Collection
    {
        $paramters = $this->mapGemParamters();
        $options = ['All', ...$paramters->map(fn (GameMapGemParamter $paramter): string => $paramter->gameMap->name.' - '.$paramter->name)->all()];
        $choice = $this->askForChoice('Select a map gem profile to generate.', $options);

        if ($choice === 'All') {
            return $paramters;
        }

        return $paramters->filter(fn (GameMapGemParamter $paramter): bool => $choice === $paramter->gameMap->name.' - '.$paramter->name)->values();
    }

    private function chooseLocationGemParamters(): Collection
    {
        $paramters = $this->locationGemParamters();
        $options = ['All', ...$paramters->map(fn (GameLocationGemParamter $paramter): string => $paramter->location->nameWithPlaneForLocationGem.' - '.$paramter->name)->all()];
        $choice = $this->askForChoice('Select a location gem profile to generate.', $options);

        if ($choice === 'All') {
            return $paramters;
        }

        return $paramters->filter(fn (GameLocationGemParamter $paramter): bool => $choice === $paramter->location->nameWithPlaneForLocationGem.' - '.$paramter->name)->values();
    }

    private function mapGemParamters(): Collection
    {
        return GameMapGemParamter::with(['gameMap', 'generatedMap'])
            ->orderBy('name')
            ->get();
    }

    private function locationGemParamters(): Collection
    {
        return GameLocationGemParamter::with(['location.map', 'generatedMap'])
            ->orderBy('name')
            ->get();
    }

    private function showSummary(array $summary): void
    {
        $this->info('Generated gem maps created: '.$summary['created']);
        $this->info('Generated gem maps skipped: '.$summary['skipped']);
        $this->info('Finished generating gem maps.');
    }

    private function askForChoice(string $question, array $options): string
    {
        $this->line('Choices: '.implode(', ', $options));

        $choice = $this->ask($question);

        while (! in_array($choice, $options, true)) {
            $this->error('Select one of: '.implode(', ', $options));
            $choice = $this->ask($question);
        }

        return $choice;
    }

    private function generateMapGemParamters(Collection $paramters): array
    {
        $summary = [
            'created' => 0,
            'skipped' => 0,
        ];

        foreach ($paramters as $paramter) {
            if (is_null($this->gemWorldGenerationService->generateMapGem($paramter))) {
                $summary['skipped']++;
                $this->line('Skipped existing generated map for: '.$paramter->name);

                continue;
            }

            $summary['created']++;
            $this->info('Generated map gem world for: '.$paramter->name);
        }

        return $summary;
    }

    private function generateLocationGemParamters(Collection $paramters): array
    {
        $summary = [
            'created' => 0,
            'skipped' => 0,
        ];

        foreach ($paramters as $paramter) {
            if (is_null($this->gemWorldGenerationService->generateLocationGem($paramter))) {
                $summary['skipped']++;
                $this->line('Skipped existing generated map for: '.$paramter->name);

                continue;
            }

            $summary['created']++;
            $this->info('Generated location gem world for: '.$paramter->name);
        }

        return $summary;
    }
}
