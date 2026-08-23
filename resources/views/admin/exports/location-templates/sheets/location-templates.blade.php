<table>
    <thead>
        <tr>
            <th>name</th>
            <th>description</th>
            <th>type</th>
            <th>is_port</th>
            <th>can_players_enter</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($locationTemplates as $locationTemplate)
            <tr>
                <td>{{ $locationTemplate->name }}</td>
                <td>{{ $locationTemplate->description }}</td>
                <td>{{ $locationTemplate->type }}</td>
                <td>{{ $locationTemplate->is_port ? 1 : 0 }}</td>
                <td>{{ $locationTemplate->can_players_enter ? 1 : 0 }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
