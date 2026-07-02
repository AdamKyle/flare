<?php

namespace App\Flare\GemWorldGeneration\Services;

use App\Flare\GemWorldGeneration\Values\GeneratedGemMapType;
use App\Flare\GemWorldGeneration\Values\GemWorldLocationPlacement;
use App\Flare\Models\GameLocationGemParamter;
use App\Flare\Models\GameMap;
use App\Flare\Models\GameMapGemParamter;
use App\Flare\Models\Location;
use App\Flare\Models\LocationTemplate;
use App\Flare\Values\LocationTemplateType;
use App\Flare\Values\LocationType;
use Illuminate\Support\Collection;

class GemWorldGenerationService
{
    public function __construct(
        private readonly GemWorldImageGenerator $imageGenerator,
        private readonly GemWorldLocationPlacementService $placementService,
    ) {}

    public function generateMapGem(GameMapGemParamter $gemParamter): ?GameMap
    {
        if (! is_null($gemParamter->generatedMap)) {
            return null;
        }

        return $this->generate(
            $gemParamter->gameMap,
            $gemParamter->name,
            GeneratedGemMapType::MAP_GEM,
            $gemParamter,
            null,
        );
    }

    public function generateLocationGem(GameLocationGemParamter $gemParamter): ?GameMap
    {
        if (! is_null($gemParamter->generatedMap)) {
            return null;
        }

        $gemParamter->loadMissing('location.map');

        return $this->generate(
            $gemParamter->location->map,
            $gemParamter->name,
            GeneratedGemMapType::LOCATION_GEM,
            null,
            $gemParamter,
        );
    }

    /**
     * @return array{created:int, skipped:int}
     */
    public function generateMapGems(Collection $gemParamters): array
    {
        return $this->generateMany($gemParamters, fn (GameMapGemParamter $gemParamter): ?GameMap => $this->generateMapGem($gemParamter));
    }

    /**
     * @return array{created:int, skipped:int}
     */
    public function generateLocationGems(Collection $gemParamters): array
    {
        return $this->generateMany($gemParamters, fn (GameLocationGemParamter $gemParamter): ?GameMap => $this->generateLocationGem($gemParamter));
    }

    private function generate(
        GameMap $parentMap,
        string $profileName,
        GeneratedGemMapType $type,
        ?GameMapGemParamter $mapGemParamter,
        ?GameLocationGemParamter $locationGemParamter,
    ): GameMap {
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

        $this->createLocations($generatedMap, $profileName);

        return $generatedMap;
    }

    private function generatedMapName(string $profileName, GeneratedGemMapType $type): string
    {
        return $profileName.' '.$type->label().' World';
    }

    private function createLocations(GameMap $generatedMap, string $profileName): void
    {
        $templates = $this->templatesByType($profileName);

        foreach ($this->placementService->placements($generatedMap) as $placement) {
            $template = $templates[$placement->type]->shift();

            if (is_null($template)) {
                continue;
            }

            $this->createLocationFromTemplate($generatedMap, $template, $placement);
        }
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
            'enemy_strength_type' => null,
            'type' => $this->locationTypeForTemplate($template),
            'x' => $placement->x,
            'y' => $placement->y,
            'minutes_between_delve_fights' => $template->type === LocationTemplateType::DELVE->value ? 5 : null,
            'delve_enemy_strength_increase' => $template->type === LocationTemplateType::DELVE->value ? 0.05 : null,
        ]);
    }

    private function locationTypeForTemplate(LocationTemplate $template): ?int
    {
        if ($template->type === LocationTemplateType::SPECIAL->value) {
            return LocationType::SPECIAL->value;
        }

        if ($template->type === LocationTemplateType::DELVE->value) {
            return LocationType::CAVE_OF_MEMORIES->value;
        }

        return null;
    }

    private function generateMany(Collection $gemParamters, callable $generator): array
    {
        $created = 0;
        $skipped = 0;

        foreach ($gemParamters as $gemParamter) {
            if (is_null($generator($gemParamter))) {
                $skipped++;

                continue;
            }

            $created++;
        }

        return [
            'created' => $created,
            'skipped' => $skipped,
        ];
    }
}
