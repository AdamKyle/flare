@php
    $fields = [
        'character_xp_bonus_range',
        'character_class_rank_xp_bonus_range',
        'kingdom_passive_training_reduction_range',
        'gold_gain_range',
        'gold_dust_gain_range',
        'shards_gain_range',
        'copper_coin_gain_range',
        'character_class_specialty_xp_gain_range',
        'crafting_skill_bonus_range',
        'item_drop_chance_increase_range',
        'unique_item_drop_chance_increase_range',
        'mythic_item_drop_chance_increase_range',
        'cosmic_item_drop_chance_increase_range',
        'ascended_item_drop_chance_increase_range',
        'character_power_reduction_range',
        'enemy_strength_increase_range',
        'enemy_healing_increase_range',
        'enemy_spell_evasion_range',
        'enemy_affix_resistance_range',
        'enemy_entrancing_chance_range',
        'enemy_devouring_light_chance_range',
        'enemy_devouring_darkness_chance_range',
        'enemy_ambush_chance_range',
        'enemy_ambush_resistance_range',
        'enemy_counter_chance_range',
        'enemy_counter_resistance_range',
        'enemy_quest_item_drop_chance_increase_range',
        'monster_xp_increase_range',
        'monster_gold_drop_increase_range',
        'faction_point_increase_range',
    ];
    $atonementNames = \App\Game\Gems\Values\GemTypeValue::getNames();
@endphp

<table>
    <thead>
        <tr>
            <th>id</th>
            <th>name</th>
            <th>description</th>
            <th>game_map_name</th>
            <th>crafting_skill_names</th>
            @foreach($fields as $field)
                <th>{{ $field }}</th>
            @endforeach
            <th>monster_atonement</th>
            <th>monster_atonement_range</th>
        </tr>
    </thead>
    <tbody>
        @foreach($mapGems as $mapGem)
            <tr>
                <td>{{ $mapGem->id }}</td>
                <td>{{ $mapGem->name }}</td>
                <td>{{ $mapGem->description }}</td>
                <td>{{ $mapGem->gameMap->name }}</td>
                <td>{{ \App\Flare\Models\GameSkill::whereIn('id', $mapGem->crafting_skill_ids ?? [])->orderBy('name')->pluck('name')->implode(', ') }}</td>
                @foreach($fields as $field)
                    <td>{{ $mapGem->{$field} }}</td>
                @endforeach
                <td>{{ is_null($mapGem->monster_atonement) ? '' : $atonementNames[$mapGem->monster_atonement] }}</td>
                <td>{{ $mapGem->monster_atonement_range }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
