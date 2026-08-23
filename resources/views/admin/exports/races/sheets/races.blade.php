<table>
    <thead>
        <tr>
            <th>name</th>
            <th>str_mod</th>
            <th>dur_mod</th>
            <th>dex_mod</th>
            <th>chr_mod</th>
            <th>int_mod</th>
            <th>agi_mod</th>
            <th>focus_mod</th>
            <th>accuracy_mod</th>
            <th>dodge_mod</th>
            <th>defense_mod</th>
            <th>looting_mod</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($gameRaces as $gameRace)
            <tr>
                <td>{{ $gameRace->name }}</td>
                <td>{{ $gameRace->str_mod }}</td>
                <td>{{ $gameRace->dur_mod }}</td>
                <td>{{ $gameRace->dex_mod }}</td>
                <td>{{ $gameRace->chr_mod }}</td>
                <td>{{ $gameRace->int_mod }}</td>
                <td>{{ $gameRace->agi_mod }}</td>
                <td>{{ $gameRace->focus_mod }}</td>
                <td>{{ $gameRace->accuracy_mod }}</td>
                <td>{{ $gameRace->dodge_mod }}</td>
                <td>{{ $gameRace->defense_mod }}</td>
                <td>{{ $gameRace->looting_mod }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
