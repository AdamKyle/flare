<table>
    <thead>
        <tr>
            <th>id</th>
            <th>Building ID</th>
            <th>Unit ID</th>
            <th>Required Level</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($buildingUnits as $buildingUnit)
            <tr>
                <th>{{ $buildingUnit->id }}</th>
                <td>{{ $buildingUnit->gameBuilding->name }}</td>
                <td>{{ $buildingUnit->gameUnit->name }}</td>
                <td>{{ $buildingUnit->required_level }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
