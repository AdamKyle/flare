<?php

namespace App\Console\AfterDevelopment;

use App\Admin\Services\AdminGemRollService;
use App\Flare\GemWorldGeneration\Services\GemWorldGenerationService;
use App\Flare\GemWorldGeneration\Values\GemWorldGenerationResult;
use App\Flare\Models\GameLocationGemParamter;
use App\Flare\Models\GameMapGemParamter;
use App\Game\Monsters\Services\BuildMonsterCacheService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class CreateGemWorlds extends Command
{
    protected $signature = 'create:gem-worlds {--generate-missing : Generate Gem World images/tiles for profiles missing both live and committed backup assets}';

    protected $description = 'Rolls missing Map and Location Gems and restores/generates their generated Gem Worlds.';

    private int $mapRollsCreated = 0;

    private int $mapRollsSkipped = 0;

    private int $locationRollsCreated = 0;

    private int $locationRollsSkipped = 0;

    public function __construct(
        private readonly AdminGemRollService $adminGemRollService,
        private readonly GemWorldGenerationService $gemWorldGenerationService,
        private readonly BuildMonsterCacheService $buildMonsterCacheService,
    ) {
        parent::__construct();
    }

    /**
     * Roll every missing Map/Location Gem and restore/generate their Gem Worlds.
     */
    public function handle(): int
    {
        $generateMissing = $this->option('generate-missing');

        $results = $this->processMapGemProfiles($generateMissing)->merge($this->processLocationGemProfiles($generateMissing));

        if (($this->mapRollsCreated + $this->locationRollsCreated) > 0) {
            $this->buildMonsterCacheService->invalidateGemAffectedCaches();
        }

        $this->showSummary($results);

        if ($results->contains(fn (GemWorldGenerationResult $result): bool => $result->failed() || $result->missingBackup())) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Roll missing Map Gems and restore/generate their Gem Worlds for every Map Gem profile.
     */
    private function processMapGemProfiles(bool $generateMissing): Collection
    {
        $profiles = GameMapGemParamter::with(['gameMap', 'generatedMap', 'rolledGem'])
            ->orderBy('name')
            ->orderBy('id')
            ->get();

        return $profiles->map(fn (GameMapGemParamter $profile): GemWorldGenerationResult => $this->processMapGemProfile($profile, $generateMissing))->values();
    }

    /**
     * Roll a Map Gem profile when missing, then restore/generate its Gem World.
     */
    private function processMapGemProfile(GameMapGemParamter $profile, bool $generateMissing): GemWorldGenerationResult
    {
        $label = $profile->gameMap->name.' - '.$profile->name;
        $profile = $this->rollMapGemIfMissing($profile, $label);

        $result = $this->gemWorldGenerationService->generateMapGem($profile, $generateMissing);
        $this->reportGenerationResult($result);

        return $result;
    }

    /**
     * Roll a system Map Gem for the profile when it has no active roll, otherwise report it as skipped.
     */
    private function rollMapGemIfMissing(GameMapGemParamter $profile, string $label): GameMapGemParamter
    {
        if (! is_null($profile->rolled_gem_id)) {
            $this->mapRollsSkipped++;
            $this->line('Skipped Map Gem roll; active roll already exists: '.$label);

            return $profile;
        }

        $this->adminGemRollService->rollMapGem($profile, null);
        $this->mapRollsCreated++;
        $this->info('Rolled Map Gem: '.$label);

        return $profile->fresh(['gameMap', 'generatedMap', 'rolledGem']);
    }

    /**
     * Roll missing Location Gems and restore/generate their Gem Worlds for every Location Gem profile.
     */
    private function processLocationGemProfiles(bool $generateMissing): Collection
    {
        $profiles = GameLocationGemParamter::with(['location.map', 'generatedMap', 'rolledGem'])
            ->orderBy('name')
            ->orderBy('id')
            ->get();

        return $profiles->map(fn (GameLocationGemParamter $profile): GemWorldGenerationResult => $this->processLocationGemProfile($profile, $generateMissing))->values();
    }

    /**
     * Roll a Location Gem profile when missing, then restore/generate its Gem World.
     */
    private function processLocationGemProfile(GameLocationGemParamter $profile, bool $generateMissing): GemWorldGenerationResult
    {
        $label = $profile->location->nameWithPlaneForLocationGem.' - '.$profile->name;
        $profile = $this->rollLocationGemIfMissing($profile, $label);

        $result = $this->gemWorldGenerationService->generateLocationGem($profile, $generateMissing);
        $this->reportGenerationResult($result);

        return $result;
    }

    /**
     * Roll a system Location Gem for the profile when it has no active roll, otherwise report it as skipped.
     */
    private function rollLocationGemIfMissing(GameLocationGemParamter $profile, string $label): GameLocationGemParamter
    {
        if (! is_null($profile->rolled_gem_id)) {
            $this->locationRollsSkipped++;
            $this->line('Skipped Location Gem roll; active roll already exists: '.$label);

            return $profile;
        }

        $this->adminGemRollService->rollLocationGem($profile, null);
        $this->locationRollsCreated++;
        $this->info('Rolled Location Gem: '.$label);

        return $profile->fresh(['location.map', 'generatedMap', 'rolledGem']);
    }

    /**
     * Output the command result for one Gem World generation using its existing status message.
     */
    private function reportGenerationResult(GemWorldGenerationResult $result): void
    {
        if ($result->generated()) {
            $this->info($result->message);

            return;
        }

        if ($result->skipped()) {
            $this->line($result->message);

            return;
        }

        if ($result->missingBackup()) {
            $this->warn($result->message);

            return;
        }

        $this->error($result->message);
    }

    /**
     * Output the factual roll and generation totals for the completed run.
     */
    private function showSummary(Collection $results): void
    {
        $this->info('Map Gem rolls created: '.$this->mapRollsCreated);
        $this->info('Map Gem rolls skipped: '.$this->mapRollsSkipped);
        $this->info('Location Gem rolls created: '.$this->locationRollsCreated);
        $this->info('Location Gem rolls skipped: '.$this->locationRollsSkipped);

        $this->info('Gem Worlds generated: '.$results->filter(fn (GemWorldGenerationResult $result): bool => $result->generated())->count());
        $this->info('Gem Worlds skipped: '.$results->filter(fn (GemWorldGenerationResult $result): bool => $result->skipped())->count());
        $this->info('Gem Worlds missing backup: '.$results->filter(fn (GemWorldGenerationResult $result): bool => $result->missingBackup())->count());
        $this->info('Gem Worlds failed: '.$results->filter(fn (GemWorldGenerationResult $result): bool => $result->failed())->count());
    }
}
