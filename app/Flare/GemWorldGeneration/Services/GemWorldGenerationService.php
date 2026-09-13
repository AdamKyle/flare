<?php

namespace App\Flare\GemWorldGeneration\Services;

use App\Flare\GemWorldGeneration\Exceptions\CouldNotPlaceGeneratedGemWorldLocation;
use App\Flare\GemWorldGeneration\Values\GemWorldGenerationResult;
use App\Flare\GemWorldGeneration\Values\GemWorldLocationPlacement;
use App\Flare\GemWorldGeneration\Values\GeneratedGemMapPath;
use App\Flare\GemWorldGeneration\Values\GeneratedGemMapType;
use App\Flare\MapGenerator\Services\MapBackupAssetService;
use App\Flare\MapGenerator\Services\MapTileGenerationService;
use App\Flare\MapGenerator\Values\MapBackupAssetResult;
use App\Flare\MapGenerator\Values\MapBackupAssetStatus;
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
    /**
     * @param GemWorldImageGenerator $imageGenerator
     * @param GemWorldLocationPlacementService $placementService
     * @param MapTileGenerationService $mapTileGenerationService
     * @param MapBackupAssetService $mapBackupAssetService
     */
    public function __construct(
        private readonly GemWorldImageGenerator $imageGenerator,
        private readonly GemWorldLocationPlacementService $placementService,
        private readonly MapTileGenerationService $mapTileGenerationService,
        private readonly MapBackupAssetService $mapBackupAssetService,
    ) {}

    /**
     * Ensure the generated Map Gem World exists, synchronizing its assets and Locations.
     *
     * @param GameMapGemParamter $gemParamter
     * @param bool $generateWhenMissing
     * @return GemWorldGenerationResult
     */
    public function generateMapGem(GameMapGemParamter $gemParamter, bool $generateWhenMissing = false): GemWorldGenerationResult
    {
        $startedAt = now();

        if (! is_null($gemParamter->generatedMap)) {
            return $this->syncExistingGeneratedMap(
                $gemParamter->generatedMap,
                $gemParamter->name,
                $gemParamter->gem_world_name,
                $this->mapGemProfileLabel($gemParamter),
                GeneratedGemMapType::MAP_GEM,
                $generateWhenMissing,
                $startedAt,
            );
        }

        return $this->generate(
            $gemParamter->gameMap,
            $gemParamter->name,
            $gemParamter->gem_world_name,
            $this->mapGemProfileLabel($gemParamter),
            GeneratedGemMapType::MAP_GEM,
            $gemParamter,
            null,
            $generateWhenMissing,
            $startedAt,
        );
    }

    /**
     * Ensure the generated Location Gem World exists, synchronizing its assets and Locations.
     *
     * @param GameLocationGemParamter $gemParamter
     * @param bool $generateWhenMissing
     * @return GemWorldGenerationResult
     */
    public function generateLocationGem(GameLocationGemParamter $gemParamter, bool $generateWhenMissing = false): GemWorldGenerationResult
    {
        $startedAt = now();

        if (! is_null($gemParamter->generatedMap)) {
            return $this->syncExistingGeneratedMap(
                $gemParamter->generatedMap,
                $gemParamter->name,
                $gemParamter->gem_world_name,
                $this->locationGemProfileLabel($gemParamter),
                GeneratedGemMapType::LOCATION_GEM,
                $generateWhenMissing,
                $startedAt,
            );
        }

        $gemParamter->loadMissing('location.map');

        return $this->generate(
            $gemParamter->location->map,
            $gemParamter->name,
            $gemParamter->gem_world_name,
            $this->locationGemProfileLabel($gemParamter),
            GeneratedGemMapType::LOCATION_GEM,
            null,
            $gemParamter,
            $generateWhenMissing,
            $startedAt,
        );
    }

    /**
     * Ensure every Map Gem profile's generated Gem World exists.
     *
     * @param Collection $gemParamters
     * @param bool $generateWhenMissing
     * @return Collection
     */
    public function generateMapGems(Collection $gemParamters, bool $generateWhenMissing = false): Collection
    {
        return $this->generateMany($gemParamters, fn (GameMapGemParamter $gemParamter): GemWorldGenerationResult => $this->generateMapGem($gemParamter, $generateWhenMissing));
    }

    /**
     * Ensure every Location Gem profile's generated Gem World exists.
     *
     * @param Collection $gemParamters
     * @param bool $generateWhenMissing
     * @return Collection
     */
    public function generateLocationGems(Collection $gemParamters, bool $generateWhenMissing = false): Collection
    {
        return $this->generateMany($gemParamters, fn (GameLocationGemParamter $gemParamter): GemWorldGenerationResult => $this->generateLocationGem($gemParamter, $generateWhenMissing));
    }

    /**
     * Synchronize an existing generated Gem World's assets, then recover its Locations only when
     * those assets are valid and Locations are not already present.
     *
     * @param GameMap $generatedMap
     * @param string $profileName
     * @param string $displayName
     * @param string $profileLabel
     * @param GeneratedGemMapType $type
     * @param bool $generateWhenMissing
     * @param CarbonInterface $startedAt
     * @return GemWorldGenerationResult
     */
    private function syncExistingGeneratedMap(
        GameMap $generatedMap,
        string $profileName,
        string $displayName,
        string $profileLabel,
        GeneratedGemMapType $type,
        bool $generateWhenMissing,
        CarbonInterface $startedAt,
    ): GemWorldGenerationResult {
        $this->syncGeneratedMapDisplayName($generatedMap, $displayName);
        $repairMessage = $this->repairLegacyJpegPath($generatedMap);
        $assetResult = $this->syncAssets($generatedMap, $generateWhenMissing);

        if (! $assetResult->isSuccessful()) {
            return $this->result($profileName, $profileLabel, $type, $assetResult->status->value, $generatedMap, 0, $startedAt, $assetResult->message);
        }

        if (Location::where('game_map_id', $generatedMap->id)->count() > 0) {
            return $this->result(
                $profileName,
                $profileLabel,
                $type,
                'skipped',
                $generatedMap,
                0,
                $startedAt,
                'Skipped existing generated map for: '.$profileName,
            );
        }

        try {
            $locationsCreated = $this->createLocations($generatedMap, $profileName);
        } catch (CouldNotPlaceGeneratedGemWorldLocation $exception) {
            return $this->result($profileName, $profileLabel, $type, 'failed', $generatedMap, 0, $startedAt, $exception->getMessage());
        }

        return $this->result(
            $profileName,
            $profileLabel,
            $type,
            'generated',
            $generatedMap,
            $locationsCreated,
            $startedAt,
            trim(($repairMessage ?? '').' '.$assetResult->message.' Recovered and placed locations for existing map: '.$profileName),
        );
    }

    /**
     * Create the generated Game Map row deterministically, synchronize its assets, and create its
     * Locations only when those assets become valid.
     *
     * @param GameMap $parentMap
     * @param string $profileName
     * @param string $displayName
     * @param string $profileLabel
     * @param GeneratedGemMapType $type
     * @param GameMapGemParamter|null $mapGemParamter
     * @param GameLocationGemParamter|null $locationGemParamter
     * @param bool $generateWhenMissing
     * @param CarbonInterface $startedAt
     * @return GemWorldGenerationResult
     */
    private function generate(
        GameMap $parentMap,
        string $profileName,
        string $displayName,
        string $profileLabel,
        GeneratedGemMapType $type,
        ?GameMapGemParamter $mapGemParamter,
        ?GameLocationGemParamter $locationGemParamter,
        bool $generateWhenMissing,
        CarbonInterface $startedAt,
    ): GemWorldGenerationResult {
        $technicalAssetName = $this->technicalAssetName($profileName, $type);

        $generatedMap = GameMap::create([
            'name' => $displayName,
            'generated_asset_name' => $technicalAssetName,
            'path' => GeneratedGemMapPath::for($technicalAssetName),
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

        $assetResult = $this->syncAssets($generatedMap, $generateWhenMissing);

        if (! $assetResult->isSuccessful()) {
            return $this->result($profileName, $profileLabel, $type, $assetResult->status->value, $generatedMap, 0, $startedAt, $assetResult->message);
        }

        try {
            $locationsCreated = $this->createLocations($generatedMap, $profileName);
        } catch (CouldNotPlaceGeneratedGemWorldLocation $exception) {
            return $this->result($profileName, $profileLabel, $type, 'failed', $generatedMap, 0, $startedAt, $exception->getMessage());
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

    /**
     * Ensure the generated Game Map's image and tile pieces are valid, restoring/repairing them
     * from committed backup and, only when explicitly allowed, generating them when the backup
     * is missing.
     *
     * @param GameMap $generatedMap
     * @param bool $generateWhenMissing
     * @return MapBackupAssetResult
     */
    private function syncAssets(GameMap $generatedMap, bool $generateWhenMissing): MapBackupAssetResult
    {
        $result = $this->mapBackupAssetService->restore($generatedMap);

        if ($result->isSuccessful() || ! $generateWhenMissing || ! $result->isMissingBackup()) {
            return $result;
        }

        return $this->generateAssets($generatedMap);
    }

    /**
     * Generate the missing Gem World image and tile pieces for a generated Game Map.
     *
     * @param GameMap $generatedMap
     * @return MapBackupAssetResult
     */
    private function generateAssets(GameMap $generatedMap): MapBackupAssetResult
    {
        if (! Storage::disk('maps')->exists($generatedMap->path)) {
            $this->imageGenerator->generate($generatedMap->effectiveGameMap(), $generatedMap->generated_asset_name ?? $generatedMap->name);
        }

        $this->mapTileGenerationService->tile($generatedMap, generateWhenMissing: true);

        return new MapBackupAssetResult(
            MapBackupAssetStatus::RESTORED,
            'Generated missing Gem World assets for: '.$generatedMap->name,
        );
    }

    /**
     * Repair a legacy JPEG generated map path to PNG when one is found.
     *
     * @param GameMap $generatedMap
     * @return string|null
     */
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

    /**
     * Build the stable, deterministic technical asset name for a profile, used only to derive
     * the generated image path and tile pieces folder. This never changes even when the
     * player-facing Gem World display name changes, so committed backups remain resolvable.
     *
     * @param string $profileName
     * @param GeneratedGemMapType $type
     * @return string
     */
    private function technicalAssetName(string $profileName, GeneratedGemMapType $type): string
    {
        return $profileName.' '.$type->label().' World';
    }

    /**
     * Backfill the stable technical asset name when missing, then update the generated Game
     * Map's player-facing name to the imported Gem World Name, leaving asset identity untouched.
     *
     * @param GameMap $generatedMap
     * @param string $displayName
     * @return void
     */
    private function syncGeneratedMapDisplayName(GameMap $generatedMap, string $displayName): void
    {
        if (is_null($generatedMap->generated_asset_name)) {
            $generatedMap->generated_asset_name = $generatedMap->name;
        }

        if ($generatedMap->name !== $displayName) {
            $generatedMap->name = $displayName;
        }

        if ($generatedMap->isDirty()) {
            $generatedMap->save();
        }
    }

    /**
     * Create every placeable generated Location from the available Location Templates.
     *
     * @param GameMap $generatedMap
     * @param string $profileName
     * @return int
     */
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

    /**
     * Resolve the available Location Templates for the profile, grouped by template type.
     *
     * @param string $profileName
     * @return array
     */
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

    /**
     * Resolve the Location Template name prefix for a profile.
     *
     * @param string $profileName
     * @return string
     */
    private function templateNamePrefix(string $profileName): string
    {
        return trim(preg_replace('/[^A-Za-z0-9]+/', ' ', $profileName));
    }

    /**
     * Create one generated Location from its placement and Location Template.
     *
     * @param GameMap $generatedMap
     * @param LocationTemplate $template
     * @param GemWorldLocationPlacement $placement
     * @return Location
     */
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

    /**
     * Resolve the Location type for a Location Template.
     *
     * @param LocationTemplate $template
     * @return int|null
     */
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

    /**
     * Run the generator over every Gem profile in the collection.
     *
     * @param Collection $gemParamters
     * @param callable $generator
     * @return Collection
     */
    private function generateMany(Collection $gemParamters, callable $generator): Collection
    {
        return $gemParamters->map(fn (mixed $gemParamter): GemWorldGenerationResult => $generator($gemParamter))->values();
    }

    /**
     * Build the Gem World generation result value.
     *
     * @param string $profileName
     * @param string $profileLabel
     * @param GeneratedGemMapType $type
     * @param string $status
     * @param GameMap|null $generatedMap
     * @param int $locationsCreated
     * @param CarbonInterface $startedAt
     * @param string $message
     * @return GemWorldGenerationResult
     */
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

    /**
     * Build the display label for a Map Gem profile.
     *
     * @param GameMapGemParamter $gemParamter
     * @return string
     */
    private function mapGemProfileLabel(GameMapGemParamter $gemParamter): string
    {
        $gemParamter->loadMissing('gameMap');

        return $gemParamter->gameMap->name.' - '.$gemParamter->name;
    }

    /**
     * Build the display label for a Location Gem profile.
     *
     * @param GameLocationGemParamter $gemParamter
     * @return string
     */
    private function locationGemProfileLabel(GameLocationGemParamter $gemParamter): string
    {
        $gemParamter->loadMissing('location.map');

        return $gemParamter->location->nameWithPlaneForLocationGem.' - '.$gemParamter->name;
    }
}
