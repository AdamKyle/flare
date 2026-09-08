<?php

namespace App\Admin\MapGems\Exports\Sheets;

use App\Flare\Models\GameMapGemParamter;
use App\Flare\Models\GameSkill;
use App\Game\Gems\Values\GemTypeValue;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class MapGemsSheet implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithTitle
{
    /**
     * Return every Map Gem profile, ordered deterministically by name.
     */
    public function collection(): Collection
    {
        return GameMapGemParamter::with('gameMap')->orderBy('name')->orderBy('id')->get();
    }

    /**
     * Map a Map Gem profile into its workbook row, using human-readable Map/Skill/atonement names.
     *
     * @param  mixed  $gameMapGemParamter
     */
    public function map($gameMapGemParamter): array
    {
        $craftingSkillNames = GameSkill::whereIn('id', $gameMapGemParamter->crafting_skill_ids ?? [])
            ->orderBy('name')
            ->pluck('name')
            ->implode(', ');

        return [
            $gameMapGemParamter->id,
            $gameMapGemParamter->name,
            $gameMapGemParamter->description,
            $gameMapGemParamter->gameMap->name,
            $craftingSkillNames,
            $gameMapGemParamter->character_xp_bonus_range,
            $gameMapGemParamter->character_class_rank_xp_bonus_range,
            $gameMapGemParamter->kingdom_passive_training_reduction_range,
            $gameMapGemParamter->gold_gain_range,
            $gameMapGemParamter->gold_dust_gain_range,
            $gameMapGemParamter->shards_gain_range,
            $gameMapGemParamter->copper_coin_gain_range,
            $gameMapGemParamter->character_class_specialty_xp_gain_range,
            $gameMapGemParamter->crafting_skill_bonus_range,
            $gameMapGemParamter->item_drop_chance_increase_range,
            $gameMapGemParamter->unique_item_drop_chance_increase_range,
            $gameMapGemParamter->mythic_item_drop_chance_increase_range,
            $gameMapGemParamter->cosmic_item_drop_chance_increase_range,
            $gameMapGemParamter->character_power_reduction_range,
            $gameMapGemParamter->enemy_strength_increase_range,
            $gameMapGemParamter->enemy_healing_increase_range,
            $gameMapGemParamter->enemy_spell_evasion_range,
            $gameMapGemParamter->enemy_affix_resistance_range,
            $gameMapGemParamter->enemy_entrancing_chance_range,
            $gameMapGemParamter->enemy_devouring_light_chance_range,
            $gameMapGemParamter->enemy_devouring_darkness_chance_range,
            $gameMapGemParamter->enemy_ambush_chance_range,
            $gameMapGemParamter->enemy_ambush_resistance_range,
            $gameMapGemParamter->enemy_counter_chance_range,
            $gameMapGemParamter->enemy_counter_resistance_range,
            $gameMapGemParamter->enemy_quest_item_drop_chance_increase_range,
            $gameMapGemParamter->monster_xp_increase_range,
            $gameMapGemParamter->monster_gold_drop_increase_range,
            is_null($gameMapGemParamter->monster_atonement) ? null : (GemTypeValue::getNames()[$gameMapGemParamter->monster_atonement] ?? null),
            $gameMapGemParamter->monster_atonement_range,
        ];
    }

    /**
     * Return the Map Gems workbook column headings.
     */
    public function headings(): array
    {
        return [
            'id', 'name', 'description', 'game_map_name', 'crafting_skill_names',
            'character_xp_bonus_range', 'character_class_rank_xp_bonus_range', 'kingdom_passive_training_reduction_range',
            'gold_gain_range', 'gold_dust_gain_range', 'shards_gain_range', 'copper_coin_gain_range',
            'character_class_specialty_xp_gain_range', 'crafting_skill_bonus_range',
            'item_drop_chance_increase_range', 'unique_item_drop_chance_increase_range', 'mythic_item_drop_chance_increase_range',
            'cosmic_item_drop_chance_increase_range',
            'character_power_reduction_range', 'enemy_strength_increase_range', 'enemy_healing_increase_range',
            'enemy_spell_evasion_range', 'enemy_affix_resistance_range', 'enemy_entrancing_chance_range',
            'enemy_devouring_light_chance_range', 'enemy_devouring_darkness_chance_range', 'enemy_ambush_chance_range',
            'enemy_ambush_resistance_range', 'enemy_counter_chance_range', 'enemy_counter_resistance_range',
            'enemy_quest_item_drop_chance_increase_range', 'monster_xp_increase_range', 'monster_gold_drop_increase_range',
            'monster_atonement', 'monster_atonement_range',
        ];
    }

    /**
     * Return the Map Gems workbook sheet title.
     */
    public function title(): string
    {
        return 'Map Gems';
    }
}
