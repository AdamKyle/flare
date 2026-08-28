<table>
    <thead>
        <tr>
            <th>name</th>
            <th>description</th>
            <th>default</th>
            <th>kingdom_color</th>
            <th>xp_bonus</th>
            <th>skill_training_bonus</th>
            <th>drop_chance_bonus</th>
            <th>enemy_stat_bonus</th>
            <th>character_attack_reduction</th>
            <th>required_location</th>
            <th>only_during_event_type</th>
            <th>can_traverse</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($gameMaps as $gameMap)
            <tr>
                <td>{{ $gameMap->name }}</td>
                <td>{{ $gameMap->description ?? '' }}</td>
                <td>{{ $gameMap->default ? 1 : 0 }}</td>
                <td>{{ $gameMap->kingdom_color }}</td>
                <td>{{ $gameMap->xp_bonus ?? 0 }}</td>
                <td>{{ $gameMap->skill_training_bonus ?? 0 }}</td>
                <td>{{ $gameMap->drop_chance_bonus ?? 0 }}</td>
                <td>{{ $gameMap->enemy_stat_bonus ?? 0 }}</td>
                <td>{{ $gameMap->character_attack_reduction ?? 0 }}</td>
                <td>{{ $gameMap->requiredLocation?->name ?? '' }}</td>
                <td>{{ $gameMap->only_during_event_type ?? '' }}</td>
                <td>{{ $gameMap->can_traverse ? 1 : 0 }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
