<table>
    <thead>
        <tr>
            <th>id</th>
            <th>name</th>
            <th>real_name</th>
            <th>type</th>
            <th>game_map_id</th>
            <th>x_position</th>
            <th>y_position</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($npcs as $npc)
            <tr>
                <td>{{ $npc->id }}</td>
                <td>{{ $npc->name }}</td>
                <td>{{ $npc->real_name }}</td>
                <td>{{ $npc->type }}</td>
                <td>{{ ! is_null($npc->game_map_id) ? $npc->gameMap->name : '' }}</td>
                <td>{{ $npc->x_position }}</td>
                <td>{{ $npc->y_position }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
