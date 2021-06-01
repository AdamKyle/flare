
<x-cards.card-with-title title="Attributes">
    <div class="alert alert-info mb-3 mt-2">
        <strong>Please note</strong>, these values represent the recruitment of one unit. These values are then
        multiplied by the amount of units you want to recruit. Movement time is for total units of this type.
    </div>
    <p class="mb-3 mt-3">{{$unit->description}}</p>
    @if (!is_null($building))
        @guest
            <p><strong>Can be recruited from: <a href="{{route('game.buildings.building', [
                'building' => $building->id
            ])}}">{{$building->name}}</a> At Level: {{$requiredLevel}}</strong></p>
        @else
            @if (auth()->user()->hasRole('Admin')))
                <p><strong>Can be recruited from: <a href="{{route('buildings.building', [
                    'building' => $building->id
                ])}}">{{$building->name}}</a> At Level: {{$requiredLevel}}</strong></p>
            @else
                <p><strong>Can be recruited from: <a href="{{route('game.buildings.building', [
                    'building' => $building->id
                ])}}">{{$building->name}}</a> At Level: {{$requiredLevel}}</strong></p>
            @endif
        @endguest

    @endif
    <dl>
        <dd><strong>Cost in wood</strong>:</dd>
        <dd>{{$unit->wood_cost}}</dd>
        <dd><strong>Cost in clay</strong>:</dd>
        <dd>{{$unit->clay_cost}}</dd>
        <dd><strong>Cost in stone</strong>:</dd>
        <dd>{{$unit->stone_cost}}</dd>
        <dd><strong>Cost in iron</strong>:</dd>
        <dd>{{$unit->iron_cost}}</dd>
        <dd><strong>Required population</strong>:</dd>
        <dd>{{$unit->required_population}}</dd>
    </dl>
    <hr />
    <h5>Unit Stats</h5>
    <hr />
    <dl>
        <dd><strong>Attack</strong>:</dd>
        <dd>{{$unit->attack}}</dd>
        <dd><strong>Defence</strong>:</dd>
        <dd>{{$unit->defence}}</dd>
        <dd><strong>Is Siege Weapon?</strong>:</dd>
        <dd>{{$unit->seige_weapon ? 'Yes' : 'No'}}</dd>
        <dd><strong>Can Heal?</strong>:</dd>
        <dd>{{$unit->can_heal ? 'Yes' : 'No'}}</dd>
    </dl>
    <hr />
    <h5>Time Information</h5>
    <hr />
    <dl>
        <dd><strong>Travel Time</strong>:</dd>
        <dd>{{$unit->travel_time}} Minutes</dd>
        <dd><strong>Time To Recruit</strong>:</dd>
        <dd>{{$unit->time_to_recruit}} Minutes</dd>
    </dl>
    <hr />
    <h5>Attack Details</h5>
    <hr />
    <dl>
        <dd><strong>Is Atacker?</strong>:</dd>
        <dd>{{$unit->atacker ? 'Yes' : 'No'}}</dd>
        <dd><strong>Is Defender?</strong>:</dd>
        <dd>{{$unit->defender ? 'Yes' : 'No'}}</dd>
    </dl>
</x-cards.card-with-title>
