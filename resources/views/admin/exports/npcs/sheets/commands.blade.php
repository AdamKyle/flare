<table>
    <thead>
    <tr>
        <th>npc_id</th>
        <th>command</th>
        <th>command_type</th>
    </tr>
    </thead>
    <tbody>
    @foreach($commands as $command)
        <tr>
            <td>{{$command->npc->real_name}}</td>
            <td>{{$command->command}}</td>
            <td>{{$command->command_type}}</td>
        </tr>
    @endforeach
    </tbody>
</table>
