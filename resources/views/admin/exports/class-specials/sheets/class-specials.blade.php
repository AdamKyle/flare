<table>
    <thead>
        <tr>
            <th>id</th>
            <th>game_class_id</th>
            <th>name</th>
            <th>description</th>
            <th>requires_class_rank_level</th>
            <th>specialty_damage</th>
            <th>increase_specialty_damage_per_level</th>
            <th>specialty_damage_uses_damage_stat_amount</th>
            <th>base_damage_mod</th>
            <th>base_ac_mod</th>
            <th>base_healing_mod</th>
            <th>base_spell_damage_mod</th>
            <th>health_mod</th>
            <th>base_damage_stat_increase</th>
            <th>attack_type_required</th>
            <th>spell_evasion</th>
            <th>affix_damage_reduction</th>
            <th>healing_reduction</th>
            <th>skill_reduction</th>
            <th>resistance_reduction</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($classSpecials as $classSpecial)
            <tr>
                <td>{{ $classSpecial->id }}</td>
                <td>{{ $classSpecial->game_class_id }}</td>
                <td>{{ $classSpecial->name }}</td>
                <td>{{ $classSpecial->description }}</td>
                <td>{{ $classSpecial->requires_class_rank_level }}</td>
                <td>{{ $classSpecial->specialty_damage }}</td>
                <td>
                    {{ $classSpecial->increase_specialty_damage_per_level }}
                </td>
                <td>
                    {{ $classSpecial->specialty_damage_uses_damage_stat_amount }}
                </td>
                <td>{{ $classSpecial->base_damage_mod }}</td>
                <td>{{ $classSpecial->base_ac_mod }}</td>
                <td>{{ $classSpecial->base_healing_mod }}</td>
                <td>{{ $classSpecial->base_spell_damage_mod }}</td>
                <td>{{ $classSpecial->health_mod }}</td>
                <td>{{ $classSpecial->base_damage_stat_increase }}</td>
                <td>{{ $classSpecial->attack_type_required }}</td>
                <td>{{ $classSpecial->spell_evasion }}</td>
                <td>{{ $classSpecial->affix_damage_reduction }}</td>
                <td>{{ $classSpecial->healing_reduction }}</td>
                <td>{{ $classSpecial->skill_reduction }}</td>
                <td>{{ $classSpecial->resistance_reduction }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
