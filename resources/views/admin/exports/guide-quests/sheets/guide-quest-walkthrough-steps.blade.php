<table>
    <thead>
        <tr>
            <th>guide_quest_name</th>
            <th>position</th>
            <th>title</th>
            <th>content</th>
            <th>target</th>
            <th>step_type</th>
            <th>required_event</th>
            <th>optional_if_target_missing</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($steps as $step)
            <tr>
                <td>{{ $step->guideQuest->name }}</td>
                <td>{{ $step->position }}</td>
                <td>{{ $step->title }}</td>
                <td>{{ $step->content }}</td>
                <td>{{ $step->target }}</td>
                <td>{{ $step->step_type }}</td>
                <td>{{ $step->required_event }}</td>
                <td>{{ $step->optional_if_target_missing ? '1' : '0' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
