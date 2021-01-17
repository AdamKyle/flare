<x-cards.card-with-title title="Attributes">
    <p>Initial cost to build. Cost is then multipled by level of building.</p>
    <dl>
        <dd><strong>Cost in wood</strong>:</dd>
        <dd>{{$building->wood_cost}}</dd>
        <dd><strong>Cost in clay</strong>:</dd>
        <dd>{{$building->clay_cost}}</dd>
        <dd><strong>Cost in stone</strong>:</dd>
        <dd>{{$building->stone_cost}}</dd>
        <dd><strong>Cost in iron</strong>:</dd>
        <dd>{{$building->iron_cost}}</dd>
    </dl>
    <hr />
    <h5>Increases Per Level</h5>
    <div class="alert alert-info mt-2 mb-2">
        The following would take place at level 2.
    </div>
    <hr />
    <dl>
        <dd><strong>Increases Population</strong>:</dd>
        <dd>{{!is_null($building->increase_population_amount) ? $building->increase_population_amount : 0}}</dd>
        <dd><strong>Increases Wood</strong>:</dd>
        <dd>{{!is_null($building->future_increase_wood_amount) ? $building->future_increase_wood_amount : 0}}</dd>
        <dd><strong>Increases Clay</strong>:</dd>
        <dd>{{!is_null($building->future_increase_clay_amount) ? $building->future_increase_clay_amount : 0}}</dd>
        <dd><strong>Increases Stone</strong>:</dd>
        <dd>{{!is_null($building->future_increase_stone_amount) ? $building->future_increase_stone_amount : 0}}</dd>
        <dd><strong>Increases Iron</strong>:</dd>
        <dd>{{!is_null($building->future_increase_iron_amount) ? $building->future_increase_iron_amount : 0}}</dd>
        <dd><strong>Increases Durability</strong>:</dd>
        <dd>{{!is_null($building->future_increase_durability_amount) ? $building->future_increase_durability_amount : 0}}</dd>
        <dd><strong>Increases Defense</strong>:</dd>
        <dd>{{!is_null($building->future_increase_defence_amount) ? $building->future_increase_defence_amount : 0}}</dd>
    </dl>
    @if (!is_null($building->increase_morale_amount) || !is_null($building->decrease_morale_amount))
        <hr />
        <h5>Morale</h5>
        <hr />
        <div class="alert alert-info mt-2 mb-2">
            These are applied per hour. Morale decrease only applies if this building falls to 0 durability.
        </div>
        <hr />
        <dl>
            <dd><strong>Increases Morale</strong>:</dd>
            <dd>{{$building->increase_morale_amount * 100}}%</dd>
            <dd><strong>Decrease Morale</strong>:</dd>
            <dd>{{$building->decrease_morale_amount * 100}}%</dd>
        </dl>
    @endif
    <hr />
    <h5>Recuritable Units</h5>
    <hr />
    @livewire('admin.kingdoms.units.data-table', [ 'building' => $building])
</x-cards.card-with-title>
