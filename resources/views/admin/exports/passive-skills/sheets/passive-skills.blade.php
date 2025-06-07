<table>
    <thead>
        <tr>
            <th>id</th>
            <th>name</th>
            <th>description</th>
            <th>max_level</th>
            <th>hours_per_level</th>
            <th>bonus_per_level</th>
            <th>resource_bonus_per_level</th>
            <th>capital_city_building_request_travel_time_reduction</th>
            <th>capital_city_unit_request_travel_time_reduction</th>
            <th>resource_request_time_reduction</th>
            <th>effect_type</th>
            <th>parent_skill_id</th>
            <th>unlocks_at_level</th>
            <th>is_locked</th>
            <th>is_parent</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($passiveSkills as $passiveSkill)
            <tr>
                <td>{{ $passiveSkill->id }}</td>
                <td>{{ $passiveSkill->name }}</td>
                <td>{{ $passiveSkill->description }}</td>
                <td>{{ $passiveSkill->max_level }}</td>
                <td>{{ $passiveSkill->hours_per_level }}</td>
                <td>{{ $passiveSkill->bonus_per_level }}</td>
                <td>{{ $passiveSkill->resource_bonus_per_level }}</td>
                <td>
                    {{ $passiveSkill->capital_city_building_request_travel_time_reduction }}
                </td>
                <td>
                    {{ $passiveSkill->capital_city_unit_request_travel_time_reduction }}
                </td>
                <td>{{ $passiveSkill->resource_request_time_reduction }}</td>
                <td>{{ $passiveSkill->effect_type }}</td>
                <td>
                    @if (! is_null($passiveSkill->parent_skill_id))
                        {{ $passiveSkill->parent->id }}
                    @endif
                </td>
                <td>{{ $passiveSkill->unlocks_at_level }}</td>
                <td>{{ $passiveSkill->is_locked }}</td>
                <td>{{ $passiveSkill->is_parent }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
