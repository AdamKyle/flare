<?php

namespace App\Flare\GemWorldGeneration\Services;

use App\Flare\GemWorldGeneration\Exceptions\CouldNotPlaceGeneratedGemWorldLocation;
use App\Flare\GemWorldGeneration\Values\GemWorldGenerationResult;
use App\Flare\GemWorldGeneration\Values\GemWorldLocationPlacement;
use App\Flare\GemWorldGeneration\Values\GeneratedGemMapType;
use App\Flare\MapGenerator\Services\MapTileGenerationService;
use App\Flare\Models\GameLocationGemParamter;
use App\Flare\Models\GameMap;
use App\Flare\Models\GameMapGemParamter;
use App\Flare\Models\Location;
use App\Flare\Models\LocationTemplate;
use App\Game\Maps\Values\LocationTemplateType;
use App\Game\Maps\Values\LocationType;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class GemWorldGenerationService
{
    public function __construct(
        private readonly GemWorldImageGenerator $imageGenerator,
        private readonly GemWorldLocationPlacementService $placementService,
        private readonly MapTileGenerationService $mapTileGenerationService,
    ) {}

    public function generateMapGem(GameMapGemParamter $gemParamter): GemWorldGenerationResult
    {
        $startedAt = now();

        if (! is_null($gemParamter->generatedMap)) {
            $generatedMap = $gemParamter->generatedMap;

            $this->mapTileGenerationService->tile($generatedMap);

            if (Location::where('game_map_id', $generatedMap->id)->count() === 0) {
                return $this->retryPlacements(
                    $generatedMap,
                    $gemParamter->name,
                    $this->mapGemProfileLabel($gemParamter),
                    GeneratedGemMapType::MAP_GEM,
                    $startedAt,
                );
            }

            return $this->result(
                $gemParamter->name,
                $this->mapGemProfileLabel($gemParamter),
                GeneratedGemMapType::MAP_GEM,
                'skipped',
                $generatedMap,
                0,
                $startedAt,
                'Skipped existing generated map for: '.$gemParamter->name,
            );
        }

        return $this->generate(
            $gemParamter->gameMap,
            $gemParamter->name,
            $this->mapGemProfileLabel($gemParamter),
            GeneratedGemMapType::MAP_GEM,
            $gemParamter,
            null,
            $startedAt,
        );
    }

    public function generateLocationGem(GameLocationGemParamter $gemParamter): GemWorldGenerationResult
    {
        $startedAt = now();

        if (! is_null($gemParamter->generatedMap)) {
            $generatedMap = $gemParamter->generatedMap;

            $this->mapTileGenerationService->tile($generatedMap);

            if (Location::where('game_map_id', $generatedMap->id)->count() === 0) {
                return $this->retryPlacements(
                    $generatedMap,
                    $gemParamter->name,
                    $this->locationGemProfileLabel($gemParamter),
                    GeneratedGemMapType::LOCATION_GEM,
                    $startedAt,
                );
            }

            return $this->result(
                $gemParamter->name,
                $this->locationGemProfileLabel($gemParamter),
                GeneratedGemMapType::LOCATION_GEM,
                'skipped',
                $gemParamter->generatedMap,
                0,
                $startedAt,
                'Skipped existing generated map for: '.$gemParamter->name,
            );
        }

        $gemParamter->loadMissing('location.map');

        return $this->generate(
            $gemParamter->location->map,
            $gemParamter->name,
            $this->locationGemProfileLabel($gemParamter),
            GeneratedGemMapType::LOCATION_GEM,
            null,
            $gemParamter,
            $startedAt,
        );
    }

    /**
     * @return Collection<int, GemWorldGenerationResult>
     */
    public function generateMapGems(Collection $gemParamters): Collection
    {
        return $this->generateMany($gemParamters, fn (GameMapGemParamter $gemParamter): GemWorldGenerationResult => $this->generateMapGem($gemParamter));
    }

    /**
     * @return Collection<int, GemWorldGenerationResult>
     */
    public function generateLocationGems(Collection $gemParamters): Collection
    {
        return $this->generateMany($gemParamters, fn (GameLocationGemParamter $gemParamter): GemWorldGenerationResult => $this->generateLocationGem($gemParamter));
    }

    private function generate(
        GameMap $parentMap,
        string $profileName,
        string $profileLabel,
        GeneratedGemMapType $type,
        ?GameMapGemParamter $mapGemParamter,
        ?GameLocationGemParamter $locationGemParamter,
        CarbonInterface $startedAt,
    ): GemWorldGenerationResult {
        $mapName = $this->generatedMapName($profileName, $type);
        $path = $this->imageGenerator->generate($parentMap, $mapName);

        $generatedMap = GameMap::create([
            'name' => $mapName,
            'path' => $path,
            'default' => false,
            'kingdom_color' => $parentMap->kingdom_color,
            'xp_bonus' => $parentMap->xp_bonus,
            'skill_training_bonus' => $parentMap->skill_training_bonus,
            'drop_chance_bonus' => $parentMap->drop_chance_bonus,
            'enemy_stat_bonus' => $parentMap->enemy_stat_bonus,
            'character_attack_reduction' => $parentMap->character_attack_reduction,
            'required_location_id' => $parentMap->required_location_id,
            'only_during_event_type' => $parentMap->only_during_event_type,
            'can_traverse' => false,
            'generated_map_type' => $type->value,
            'generated_parent_game_map_id' => $parentMap->id,
            'game_map_gem_paramter_id' => $mapGemParamter?->id,
            'game_location_gem_paramter_id' => $locationGemParamter?->id,
        ]);

        $this->mapTileGenerationService->tile($generatedMap);

        try {
            $locationsCreated = $this->createLocations($generatedMap, $profileName);
        } catch (CouldNotPlaceGeneratedGemWorldLocation $exception) {
            return $this->result(
                $profileName,
                $profileLabel,
                $type,
                'failed',
                $generatedMap,
                0,
                $startedAt,
                $exception->getMessage(),
            );
        }

        return $this->result(
            $profileName,
            $profileLabel,
            $type,
            'generated',
            $generatedMap,
            $locationsCreated,
            $startedAt,
            'Generated '.$type->labelForMessage().' world for: '.$profileName,
        );
    }

    private function retryPlacements(
        GameMap $generatedMap,
        string $profileName,
        string $profileLabel,
        GeneratedGemMapType $type,
        CarbonInterface $startedAt,
    ): GemWorldGenerationResult {
        $repairMessage = $this->repairLegacyJpegPath($generatedMap);

        try {
            $locationsCreated = $this->createLocations($generatedMap, $profileName);
        } catch (CouldNotPlaceGeneratedGemWorldLocation $exception) {
            return $this->result(
                $profileName,
                $profileLabel,
                $type,
                'failed',
                $generatedMap,
                0,
                $startedAt,
                $exception->getMessage(),
            );
        }

        return $this->result(
            $profileName,
            $profileLabel,
            $type,
            'generated',
            $generatedMap,
            $locationsCreated,
            $startedAt,
            trim(($repairMessage ?? '').' Recovered and placed locations for existing map: '.$profileName),
        );
    }

    private function repairLegacyJpegPath(GameMap $generatedMap): ?string
    {
        $oldPath = $generatedMap->path;

        if (! in_array(strtolower(pathinfo($oldPath, PATHINFO_EXTENSION)), ['jpeg', 'jpg'], true)) {
            return null;
        }

        $disk = Storage::disk('maps');

        if (! $disk->exists($oldPath)) {
            return null;
        }

        $imageData = $disk->get($oldPath);
        $image = @imagecreatefromstring($imageData);

        if ($image === false) {
            return null;
        }

        imagedestroy($image);

        $newPath = preg_replace('/\.(jpeg|jpg)$/i', '.png', $oldPath);

        if (! $disk->exists($newPath)) {
            $disk->put($newPath, $imageData);
        }

        $generatedMap->update(['path' => $newPath]);
        $generatedMap->refresh();

        return 'Old generated map path found: '.$oldPath.'; repaired to '.$newPath.'; retrying placement only.';
    }

    private function generatedMapName(string $profileName, GeneratedGemMapType $type): string
    {
        return $profileName.' '.$type->label().' World';
    }

    private function createLocations(GameMap $generatedMap, string $profileName): int
    {
        $templates = $this->templatesByType($profileName);
        $locationsCreated = 0;

        foreach ($this->placementService->placements($generatedMap) as $placement) {
            $template = $templates[$placement->type]->shift();

            if (is_null($template)) {
                continue;
            }

            $this->createLocationFromTemplate($generatedMap, $template, $placement);
            $locationsCreated++;
        }

        return $locationsCreated;
    }

    private function templatesByType(string $profileName): array
    {
        $templates = [];

        foreach (LocationTemplateType::cases() as $type) {
            $templates[$type->value] = LocationTemplate::where('type', $type->value)
                ->where('name', 'like', $this->templateNamePrefix($profileName).'%')
                ->orderBy('id')
                ->get();

            if ($templates[$type->value]->isEmpty()) {
                $templates[$type->value] = LocationTemplate::where('type', $type->value)
                    ->orderBy('id')
                    ->get();
            }
        }

        return $templates;
    }

    private function templateNamePrefix(string $profileName): string
    {
        return trim(preg_replace('/[^A-Za-z0-9]+/', ' ', $profileName));
    }

    private function createLocationFromTemplate(GameMap $generatedMap, LocationTemplate $template, GemWorldLocationPlacement $placement): Location
    {
        return Location::create([
            'name' => $template->name,
            'game_map_id' => $generatedMap->id,
            'description' => $template->description,
            'is_port' => $template->is_port,
            'can_players_enter' => $template->can_players_enter,
            'can_auto_battle' => true,
            'type' => $this->locationTypeForTemplate($template),
            'x' => $placement->x,
            'y' => $placement->y,
            'minutes_between_delve_fights' => $template->type === LocationTemplateType::DELVE->value ? 5 : null,
        ]);
    }

    private function locationTypeForTemplate(LocationTemplate $template): ?int
    {
        if ($template->type === LocationTemplateType::SPECIAL->value) {
            return LocationType::SPECIAL->value;
        }

        if ($template->type === LocationTemplateType::DELVE->value) {
            return LocationType::CAVE_OF_SHADOWS->value;
        }

        return null;
    }

    private function generateMany(Collection $gemParamters, callable $generator): Collection
    {
        return $gemParamters->map(fn (mixed $gemParamter): GemWorldGenerationResult => $generator($gemParamter))->values();
    }

    private function result(
        string $profileName,
        string $profileLabel,
        GeneratedGemMapType $type,
        string $status,
        ?GameMap $generatedMap,
        int $locationsCreated,
        CarbonInterface $startedAt,
        string $message,
    ): GemWorldGenerationResult {
        $finishedAt = now();

        return new GemWorldGenerationResult(
            profile_name: $profileName,
            profile_label: $profileLabel,
            map_type: $type->value,
            status: $status,
            map_id: $generatedMap?->id,
            path: $generatedMap?->path,
            locations_created: $locationsCreated,
            started_at: $startedAt,
            finished_at: $finishedAt,
            elapsed_seconds: $startedAt->diffInSeconds($finishedAt),
            message: $message,
        );
    }

    private function mapGemProfileLabel(GameMapGemParamter $gemParamter): string
    {
        $gemParamter->loadMissing('gameMap');

        return $gemParamter->gameMap->name.' - '.$gemParamter->name;
    }

    private function locationGemProfileLabel(GameLocationGemParamter $gemParamter): string
    {
        $gemParamter->loadMissing('location.map');

        return $gemParamter->location->nameWithPlaneForLocationGem.' - '.$gemParamter->name;
    }
}
