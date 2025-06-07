<table>
    <thead>
        <tr>
            <th>id</th>
            <th>name</th>
            <th>str</th>
            <th>dur</th>
            <th>dex</th>
            <th>chr</th>
            <th>int</th>
            <th>agi</th>
            <th>focus</th>
            <th>ac</th>
            <th>accuracy</th>
            <th>casting_accuracy</th>
            <th>dodge</th>
            <th>criticality</th>
            <th>is_celestial_entity</th>
            <th>gold_cost</th>
            <th>gold_dust_cost</th>
            <th>can_cast</th>
            <th>max_level</th>
            <th>damage_stat</th>
            <th>xp</th>
            <th>drop_check</th>
            <th>gold</th>
            <th>shards</th>
            <th>health_range</th>
            <th>attack_range</th>
            <th>max_spell_damage</th>
            <th>max_affix_damage</th>
            <th>healing_percentage</th>
            <th>spell_evasion</th>
            <th>affix_resistance</th>
            <th>entrancing_chance</th>
            <th>devouring_light_chance</th>
            <th>devouring_darkness_chance</th>
            <th>ambush_chance</th>
            <th>ambush_resistance</th>
            <th>counter_chance</th>
            <th>counter_resistance</th>
            <th>quest_item_name</th>
            <th>quest_item_drop_chance</th>
            <th>game_map_id</th>
            <th>celestial_type</th>
            <th>fire_atonement</th>
            <th>ice_atonement</th>
            <th>water_atonement</th>
            <th>is_raid_monster</th>
            <th>is_raid_boss</th>
            <th>raid_special_attack_type</th>
            <th>life_stealing_resistance</th>
            <th>only_for_location_type</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($monsters as $monster)
            <tr>
                <td>{{ $monster->id }}</td>
                <td>{{ $monster->name }}</td>
                <td>{{ $monster->str }}</td>
                <td>{{ $monster->dur }}</td>
                <td>{{ $monster->dex }}</td>
                <td>{{ $monster->chr }}</td>
                <td>{{ $monster->int }}</td>
                <td>{{ $monster->agi }}</td>
                <td>{{ $monster->focus }}</td>
                <td>{{ $monster->ac }}</td>
                <td>{{ $monster->accuracy }}</td>
                <td>{{ $monster->casting_accuracy }}</td>
                <td>{{ $monster->dodge }}</td>
                <td>{{ $monster->criticality }}</td>
                <th>{{ $monster->is_celestial_entity }}</th>
                <th>{{ $monster->gold_cost }}</th>
                <th>{{ $monster->gold_dust_cost }}</th>
                <th>{{ $monster->can_cast }}</th>
                <td>{{ $monster->max_level }}</td>
                <td>{{ $monster->damage_stat }}</td>
                <td>{{ $monster->xp }}</td>
                <td>{{ $monster->drop_check }}</td>
                <td>{{ $monster->gold }}</td>
                <td>{{ $monster->shards }}</td>
                <td>{{ $monster->health_range }}</td>
                <td>{{ $monster->attack_range }}</td>
                <td>{{ $monster->max_spell_damage }}</td>
                <td>{{ $monster->max_affix_damage }}</td>
                <td>{{ $monster->healing_percentage }}</td>
                <td>{{ $monster->spell_evasion }}</td>
                <td>{{ $monster->affix_resistance }}</td>
                <td>{{ $monster->entrancing_chance }}</td>
                <td>{{ $monster->devouring_light_chance }}</td>
                <td>{{ $monster->devouring_darkness_chance }}</td>
                <td>{{ $monster->ambush_chance }}</td>
                <td>{{ $monster->ambush_resistance }}</td>
                <td>{{ $monster->counter_chance }}</td>
                <td>{{ $monster->counter_resistance }}</td>
                <td>
                    {{ ! is_null($monster->questItem) ? $monster->questItem->name : null }}
                </td>
                <td>{{ $monster->quest_item_drop_chance }}</td>
                <td>
                    {{ ! is_null($monster->game_map_id) ? $monster->gameMap->name : null }}
                </td>
                <td>{{ $monster->celestial_type }}</td>
                <td>{{ $monster->fire_atonement }}</td>
                <td>{{ $monster->ice_atonement }}</td>
                <td>{{ $monster->water_atonement }}</td>
                <td>{{ $monster->is_raid_monster }}</td>
                <td>{{ $monster->is_raid_boss }}</td>
                <td>{{ $monster->raid_special_attack_type }}</td>
                <td>{{ $monster->life_stealing_resistance }}</td>
                <td>{{ $monster->only_for_location_type }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
