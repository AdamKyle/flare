<table>
    <thead>
        <tr>
            <th>id</th>
            <th>name</th>
            <th>description</th>
            <th>max_level</th>
            <th>base_durability</th>
            <th>base_defence</th>
            <th>required_population</th>
            <th>units_per_level</th>
            <th>only_at_level</th>
            <th>is_resource_building</th>
            <th>trains_units</th>
            <th>is_walls</th>
            <th>is_church</th>
            <th>is_farm</th>
            <th>wood_cost</th>
            <th>clay_cost</th>
            <th>stone_cost</th>
            <th>iron_cost</th>
            <th>steel_cost</th>
            <th>time_to_build</th>
            <th>time_increase_amount</th>
            <th>decrease_morale_amount</th>
            <th>increase_population_amount</th>
            <th>increase_morale_amount</th>
            <th>increase_wood_amount</th>
            <th>increase_clay_amount</th>
            <th>increase_stone_amount</th>
            <th>increase_iron_amount</th>
            <th>increase_durability_amount</th>
            <th>increase_defence_amount</th>
            <th>is_locked</th>
            <th>is_special</th>
            <th>passive_skill_id</th>
            <th>level_required</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($buildings as $building)
            <tr>
                <td>{{ $building->id }}</td>
                <td>{{ $building->name }}</td>
                <td>{{ $building->description }}</td>
                <td>{{ $building->max_level }}</td>
                <td>{{ $building->base_durability }}</td>
                <td>{{ $building->base_defence }}</td>
                <td>{{ $building->required_population }}</td>
                <td>{{ $building->units_per_level }}</td>
                <td>{{ $building->only_at_level }}</td>
                <td>{{ $building->is_resource_building }}</td>
                <td>{{ $building->trains_units }}</td>
                <td>{{ $building->is_walls }}</td>
                <td>{{ $building->is_church }}</td>
                <td>{{ $building->is_farm }}</td>
                <td>{{ $building->wood_cost }}</td>
                <td>{{ $building->clay_cost }}</td>
                <td>{{ $building->stone_cost }}</td>
                <td>{{ $building->iron_cost }}</td>
                <td>{{ $building->steel_cost }}</td>
                <td>{{ $building->time_to_build }}</td>
                <td>{{ $building->time_increase_amount }}</td>
                <td>{{ $building->decrease_morale_amount }}</td>
                <td>{{ $building->increase_population_amount }}</td>
                <td>{{ $building->increase_morale_amount }}</td>
                <td>{{ $building->increase_wood_amount }}</td>
                <td>{{ $building->increase_clay_amount }}</td>
                <td>{{ $building->increase_stone_amount }}</td>
                <td>{{ $building->increase_iron_amount }}</td>
                <td>{{ $building->increase_durability_amount }}</td>
                <td>{{ $building->increase_defence_amount }}</td>
                <td>{{ $building->is_locked }}</td>
                <td>{{ $building->is_special }}</td>
                <td>
                    @if (! is_null($building->passive_skill_id))
                        {{ $building->passive->name }}
                    @endif
                </td>
                <td>{{ $building->level_required }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
