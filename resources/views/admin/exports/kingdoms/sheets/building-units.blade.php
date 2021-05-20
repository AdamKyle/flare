<table>
    <thead>
    <tr>
        <th>Building Name</th>
        <th>Unit Name</th>
        <th>Required Level</th>
    </tr>
    </thead>
    <tbody>
    @foreach($buildingUnits as $buildingUnit)
        <tr>
            <td>{{$buildingUnit->gameBuilding->name}}</td>
            <td>{{$buildingUnit->gameUnit->name}}</td>
            <td>{{$buildingUnit->required_level}}</td>
        </tr>
    @endforeach
    </tbody>
</table>
