<?php

namespace App\Admin\Console\Commands;

use App\Flare\GemWorldGeneration\Services\GemWorldGenerationService;
use App\Flare\GemWorldGeneration\Values\GemWorldGenerationResult;
use App\Flare\Models\GameLocationGemParamter;
use App\Flare\Models\GameMapGemParamter;
use Carbon\CarbonInterface;
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
        $choice = $this->choice('What do you want to generate?', self::GENERATION_CHOICES);
        $startedAt = now();

        if ($choice === 'Map Gem') {
            $results = $this->generateGemParamters($this->chooseMapGemParamters()->map(fn (GameMapGemParamter $paramter): array => [
                'type' => 'map',
                'paramter' => $paramter,
            ]));
            $this->showSummary($results, $startedAt);

            return self::SUCCESS;
        }

        if ($choice === 'Location Gem') {
            $results = $this->generateGemParamters($this->chooseLocationGemParamters()->map(fn (GameLocationGemParamter $paramter): array => [
                'type' => 'location',
                'paramter' => $paramter,
            ]));
            $this->showSummary($results, $startedAt);

            return self::SUCCESS;
        }

        $results = $this->generateGemParamters($this->mapGemParamters()
            ->map(fn (GameMapGemParamter $paramter): array => [
                'type' => 'map',
                'paramter' => $paramter,
            ])
            ->merge($this->locationGemParamters()->map(fn (GameLocationGemParamter $paramter): array => [
                'type' => 'location',
                'paramter' => $paramter,
            ])));

        $this->showSummary($results, $startedAt);

        return self::SUCCESS;
    }

    private function chooseMapGemParamters(): Collection
    {
        $paramters = $this->mapGemParamters();
        $profileLabelsById = $paramters->mapWithKeys(fn (GameMapGemParamter $paramter): array => [
            $paramter->id => $this->mapGemProfileLabel($paramter),
        ]);
        $choice = $this->choice('Select a map gem profile to generate.', ['All', ...$profileLabelsById->values()->all()]);

        if ($choice === 'All') {
            return $paramters;
        }

        $selectedId = $profileLabelsById->search($choice, true);

        return $paramters->where('id', $selectedId)->values();
    }

    private function chooseLocationGemParamters(): Collection
    {
        $paramters = $this->locationGemParamters();
        $profileLabelsById = $paramters->mapWithKeys(fn (GameLocationGemParamter $paramter): array => [
            $paramter->id => $this->locationGemProfileLabel($paramter),
        ]);
        $choice = $this->choice('Select a location gem profile to generate.', ['All', ...$profileLabelsById->values()->all()]);

        if ($choice === 'All') {
            return $paramters;
        }

        $selectedId = $profileLabelsById->search($choice, true);

        return $paramters->where('id', $selectedId)->values();
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

    private function showSummary(Collection $results, CarbonInterface $startedAt): void
    {
        $this->info('Generated gem maps created: '.$results->filter(fn (GemWorldGenerationResult $result): bool => $result->generated())->count());
        $this->info('Generated gem maps skipped: '.$results->filter(fn (GemWorldGenerationResult $result): bool => $result->skipped())->count());

        $failed = $results->filter(fn (GemWorldGenerationResult $result): bool => $result->failed());

        if ($failed->isNotEmpty()) {
            $this->warn('Generated gem maps failed: '.$failed->count());
        }

        $this->info('Finished generating gem maps in '.$this->formatElapsed($startedAt->diffInSeconds(now())).'.');
    }

    private function generateGemParamters(Collection $items): Collection
    {
        $total = $items->count();
        $this->info('Generating '.$total.' gem '.($total === 1 ? 'map' : 'maps').'...');

        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();
        $this->newLine();

        $results = collect();

        foreach ($items->values() as $index => $item) {
            $paramter = $item['paramter'];
            $label = $item['type'] === 'map' ? $this->mapGemProfileLabel($paramter) : $this->locationGemProfileLabel($paramter);

            $this->line('['.($index + 1).'/'.$total.'] '.$label);
            $this->line('Stage: generating image');
            $this->line('Stage: creating game map record');
            $this->line('Stage: placing generated locations');

            $result = $item['type'] === 'map'
                ? $this->gemWorldGenerationService->generateMapGem($paramter)
                : $this->gemWorldGenerationService->generateLocationGem($paramter);

            if ($result->generated()) {
                $this->info($result->message);
            } elseif ($result->failed()) {
                $this->error('Placement failed for: '.$label);
                $this->error($result->message);
            } else {
                $this->line($result->message);
            }

            $results->push($result);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        return $results;
    }

    private function mapGemProfileLabel(GameMapGemParamter $paramter): string
    {
        return $paramter->gameMap->name.' - '.$paramter->name;
    }

    private function locationGemProfileLabel(GameLocationGemParamter $paramter): string
    {
        return $paramter->location->nameWithPlaneForLocationGem.' - '.$paramter->name;
    }

    private function formatElapsed(int $seconds): string
    {
        return sprintf('%02d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60);
    }
}
