<?php

namespace App\Admin\LocationGems\Exports\Sheets;

use App\Flare\Models\GameLocationGemParamter;
use App\Flare\Models\GameSkill;
use App\Game\Gems\Values\GemTypeValue;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class LocationGemsSheet implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithTitle
{
    /**
     * Return every Location Gem profile, ordered deterministically by name.
     *
     * @return Collection<int, GameLocationGemParamter> Location Gem profiles to export.
     */
    public function collection(): Collection
    {
        return GameLocationGemParamter::with('location.map')->orderBy('name')->orderBy('id')->get();
    }

    /**
     * Map a Location Gem profile into its workbook row, using human-readable Map/Location/Skill/atonement names.
     *
     * @param  GameLocationGemParamter  $gameLocationGemParamter  Location Gem profile to map.
     * @return array<int, mixed> Workbook row values.
     */
    public function map($gameLocationGemParamter): array
    {
        $craftingSkillNames = GameSkill::whereIn('id', $gameLocationGemParamter->crafting_skill_ids ?? [])
            ->orderBy('name')
            ->pluck('name')
            ->implode(', ');

        return [
            $gameLocationGemParamter->id,
            $gameLocationGemParamter->name,
            $gameLocationGemParamter->description,
            $gameLocationGemParamter->location->map->name,
            $gameLocationGemParamter->location->name,
            $craftingSkillNames,
            $gameLocationGemParamter->character_xp_bonus_range,
            $gameLocationGemParamter->character_class_rank_xp_bonus_range,
            $gameLocationGemParamter->kingdom_passive_training_reduction_range,
            $gameLocationGemParamter->gold_gain_range,
            $gameLocationGemParamter->gold_dust_gain_range,
            $gameLocationGemParamter->shards_gain_range,
            $gameLocationGemParamter->copper_coin_gain_range,
            $gameLocationGemParamter->character_class_specialty_xp_gain_range,
            $gameLocationGemParamter->crafting_skill_bonus_range,
            $gameLocationGemParamter->item_drop_chance_increase_range,
            $gameLocationGemParamter->unique_item_drop_chance_increase_range,
            $gameLocationGemParamter->mythic_item_drop_chance_increase_range,
            $gameLocationGemParamter->cosmic_item_drop_chance_increase_range,
            $gameLocationGemParamter->enemy_strength_increase_range,
            $gameLocationGemParamter->enemy_healing_increase_range,
            $gameLocationGemParamter->enemy_spell_evasion_range,
            $gameLocationGemParamter->enemy_affix_resistance_range,
            $gameLocationGemParamter->enemy_entrancing_chance_range,
            $gameLocationGemParamter->enemy_devouring_light_chance_range,
            $gameLocationGemParamter->enemy_devouring_darkness_chance_range,
            $gameLocationGemParamter->enemy_ambush_chance_range,
            $gameLocationGemParamter->enemy_ambush_resistance_range,
            $gameLocationGemParamter->enemy_counter_chance_range,
            $gameLocationGemParamter->enemy_counter_resistance_range,
            $gameLocationGemParamter->enemy_quest_item_drop_chance_increase_range,
            $gameLocationGemParamter->monster_xp_increase_range,
            $gameLocationGemParamter->monster_gold_drop_increase_range,
            is_null($gameLocationGemParamter->monster_atonement) ? null : (GemTypeValue::getNames()[$gameLocationGemParamter->monster_atonement] ?? null),
            $gameLocationGemParamter->monster_atonement_range,
        ];
    }

    /**
     * Return the Location Gems workbook column headings.
     *
     * @return array<int, string> Workbook column headings.
     */
    public function headings(): array
    {
        return [
            'id', 'name', 'description', 'game_map_name', 'location_name', 'crafting_skill_names',
            'character_xp_bonus_range', 'character_class_rank_xp_bonus_range', 'kingdom_passive_training_reduction_range',
            'gold_gain_range', 'gold_dust_gain_range', 'shards_gain_range', 'copper_coin_gain_range',
            'character_class_specialty_xp_gain_range', 'crafting_skill_bonus_range',
            'item_drop_chance_increase_range', 'unique_item_drop_chance_increase_range', 'mythic_item_drop_chance_increase_range',
            'cosmic_item_drop_chance_increase_range',
            'enemy_strength_increase_range', 'enemy_healing_increase_range',
            'enemy_spell_evasion_range', 'enemy_affix_resistance_range', 'enemy_entrancing_chance_range',
            'enemy_devouring_light_chance_range', 'enemy_devouring_darkness_chance_range', 'enemy_ambush_chance_range',
            'enemy_ambush_resistance_range', 'enemy_counter_chance_range', 'enemy_counter_resistance_range',
            'enemy_quest_item_drop_chance_increase_range', 'monster_xp_increase_range', 'monster_gold_drop_increase_range',
            'monster_atonement', 'monster_atonement_range',
        ];
    }

    /**
     * Return the Location Gems workbook sheet title.
     *
     * @return string Location Gems workbook sheet title.
     */
    public function title(): string
    {
        return 'Location Gems';
    }
}
