<?php

namespace App\Admin\Import\MapGems\Sheets;

use App\Flare\Models\GameMap;
use App\Flare\Models\GameMapGemParamters;
use App\Flare\Models\GameSkill;
use App\Game\Gems\Values\GemTypeValue;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class MapGemsSheet implements ToCollection
{
    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            if ($index === 0) {
                continue;
            }

            $data = array_combine($rows[0]->toArray(), $row->toArray());
            $cleanData = $this->cleanData($data);

            if (! empty($cleanData)) {
                GameMapGemParamters::updateOrCreate(
                    ['game_map_id' => $cleanData['game_map_id']],
                    $cleanData,
                );
            }
        }
    }

    private function cleanData(array $data): array
    {
        $gameMap = GameMap::where('name', $data['game_map_name'] ?? null)->first();

        if (is_null($gameMap)) {
            return [];
        }

        $hasCraftingSkillNames = array_key_exists('crafting_skill_names', $data);
        $craftingSkillNames = $data['crafting_skill_names'] ?? null;
        $combinedRarityRange = $data['unique_mythic_cosmic_item_drop_chance_increase_range'] ?? null;

        unset(
            $data['game_map_name'],
            $data['crafting_skill_names'],
            $data['unique_mythic_cosmic_item_drop_chance_increase_range'],
            $data['id'],
        );

        if (! is_null($combinedRarityRange) && $combinedRarityRange !== '') {
            $data['unique_item_drop_chance_increase_range'] = ($data['unique_item_drop_chance_increase_range'] ?? null) ?: $combinedRarityRange;
            $data['mythic_item_drop_chance_increase_range'] = ($data['mythic_item_drop_chance_increase_range'] ?? null) ?: $combinedRarityRange;
            $data['cosmic_item_drop_chance_increase_range'] = ($data['cosmic_item_drop_chance_increase_range'] ?? null) ?: $combinedRarityRange;
        }

        if (array_key_exists('description', $data) && ! is_null($data['description'])) {
            $data['description'] = trim((string) $data['description']);
        }

        $data = array_filter($data, fn ($value) => ! is_null($value) && $value !== '');
        $data['game_map_id'] = $gameMap->id;

        if ($hasCraftingSkillNames) {
            $data['crafting_skill_ids'] = $this->resolveCraftingSkillIds($craftingSkillNames);
        }

        if (isset($data['monster_atonement'])) {
            $atonement = array_search(
                strtolower((string) $data['monster_atonement']),
                array_map('strtolower', GemTypeValue::getNames()),
                true,
            );

            if ($atonement === false) {
                unset($data['monster_atonement']);
            } else {
                $data['monster_atonement'] = $atonement;
            }
        }

        return $data;
    }

    private function resolveCraftingSkillIds(mixed $craftingSkillNames): array
    {
        $names = array_values(array_unique(array_filter(array_map(
            fn (string $name): string => trim($name),
            explode(',', (string) $craftingSkillNames),
        ))));

        return GameSkill::whereIn('name', $names)->pluck('id')->all();
    }
}
