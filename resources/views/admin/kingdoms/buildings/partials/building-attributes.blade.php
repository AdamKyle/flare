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
        These take affect when the building reaches level 2 and beyond.
    </div>
    <hr />
    <dl>
        <dd><strong>Increases Population</strong>:</dd>
        <dd>{{$building->increase_population_amount}} people</dd>
        <dd><strong>Increases Morale</strong>:</dd>
        <dd>{{$building->increase_morale_amount * 100}}%</dd>
        <dd><strong>Decrease Morale<sup>*</sup></strong>:</dd>
        <dd>{{$building->decrease_morale_amount * 100}}%</dd>
        <dd><strong>Increases Wood</strong>:</dd>
        <dd>{{$building->increase_wood_amount * 100}}%</dd>
        <dd><strong>Increases Clay</strong>:</dd>
        <dd>{{$building->increase_clay_amount * 100}}%</dd>
        <dd><strong>Increases Stone</strong>:</dd>
        <dd>{{$building->increase_stone_amount * 100}}%</dd>
        <dd><strong>Increases Iron</strong>:</dd>
        <dd>{{$building->increase_iron_amount * 100}}%</dd>
        <dd><strong>Increases Durability</strong>:</dd>
        <dd>{{$building->increase_durability_amount * 100}}%</dd>
        <dd><strong>Increases Defense</strong>:</dd>
        <dd>{{$building->increase_defence_amount * 100}}%</dd>
    </dl>
    <p class="mt-2 text-muted">
        <sup>*</sup> <small>Only is applied when the buildings durability is 0.</small>
    </p>
</x-cards.card-with-title>