<x-cards.card-with-title title="Base Details">
    <p>{{$building->description}}</p>
    <hr />
    <dl>
        <dd><strong>Max Level</strong>:</dd>
        <dd>{{$building->max_level}}</dd>
        <dd><strong>Base Durability</strong>:</dd>
        <dd>{{$building->base_durability}}</dd>
        <dd><strong>Base Defence</strong>:</dd>
        <dd>{{$building->base_defence}}</dd>
        <dd><strong>Required Population</strong>:</dd>
        <dd>{{$building->required_population}}</dd>
        <dd><strong>Is Walls?</strong>:</dd>
        <dd>{{$building->is_walls ? 'Yes' : 'No'}}</dd>
        <dd><strong>Is Farm?</strong>:</dd>
        <dd>{{$building->is_farm ? 'Yes' : 'No'}}</dd>
        <dd><strong>Is Church?</strong>:</dd>
        <dd>{{$building->is_church ? 'Yes' : 'No'}}</dd>
        <dd><strong>Does this building generate resources?</strong>:</dd>
        <dd>{{$building->is_resource_building ? 'Yes' : 'No'}}</dd>
    </dl>
</x-cards.card-with-title>

<x-cards.card-with-title title="Time Needed">
    <p>Time is increased by <em>level * time needed * percentage to increase by</em> after level 2.</p>
    <hr />
    <dl>
        <dd><strong>Time Required</strong>:</dd>
        <dd>{{$building->time_to_build}}</dd>
        <dd><strong>Time Increased By</strong>:</dd>
        <dd>{{$building->time_increase_amount * 100}}%</dd>
    </dl>
</x-cards.card-with-title>