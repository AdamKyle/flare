<div class="row page-titles">
    <div class="col-md-6 align-self-right">
        <h4 class="mt-2">{{$monster->name}}</h4>
    </div>
    <div class="col-md-6 align-self-right">
        <a href="{{url()->previous()}}" class="btn btn-primary float-right ml-2">Back</a>
    </div>
</div>
@include('admin.monsters.partials.details', [
    'monster' => $monster,
    'canEdit' => true,
])

@if ($monster->can_cast)
    <hr />
    <div class="card">
        <div class="card-body">
            <div class="alert alert-info mb-2 mt-2">
                <p>
                    The ability to cast spells is a 1/100 chance regardless of if the monster hits.
                    A characters spell evasion can help mitigate the damage. The "Max Cast For" indicates
                    the total spell damage between 1 and that number the monster can do.
                </p>
                <p>To increase spell evasion, a character simply equips rings, which gives a small boost to spell evasion.</p>
            </div>
            <dl class="mt-3">
                <dt>Max Cast For</dt>
                <dd>{{number_format($monster->max_spell_damage)}}</dd>
            </dl>
        </div>
    </div>
@endif

@if ($monster->can_use_artifacts)
    <hr />
    <div class="card">
        <div class="card-body">
            <div class="alert alert-info mb-2 mt-2">
                <p>
                    The ability to use artifacts is a 1/100 chance regardless if the monster hits.
                    A characters equipped artifacts can affect a stat called: Artifact Annulment.
                    Artifacts can raise this stat ever so slightly. Some affixes can also raise this stat.
                    The higher the state, the less damage you take.
                </p>
            </div>
            <dl class="mt-3">
                <dt>Max Cast For</dt>
                <dd>{{number_format($monster->max_artifact_damage)}}</dd>
            </dl>
        </div>
    </div>
@endif

@if ($monster->is_celestial_entity)
    <hr />
    <div class="card">
        <div class="card-body">
            <div class="alert alert-info mb-2 mt-2">
                <p>This is a celestial entity which can only be conjured via a special NPC. You can learn more about those <a href="#">here</a>.</p>
                <p>This creature will have a cost in Gold and Gold Dust and can be summoned either privately or publicly.</p>
                <p>These Creatures can also give quest rewards when defeated, other wise have a small 1% chance of dropping a item.</p>
                <p>Celestial entities also drop what are called shards. These are used in <a href="#">Alchemy</a> in place of gold.</p>
                <p>Celestial Entities can also spawn randomly on the map by a player, any player, just moving around. When these entities spawn - be it summoned or other wise, they
                spawn in random locations at which the player must then go to. If the location is a kingdom is a small chance of it doing damage to the kingdom. The chance of a beast spawning
                is greater then the chance of it doing damage to a kingdom when it does spawn.</p>
            </div>
            <dl class="mt-3">
                <dt>Gold Cost:</dt>
                <dd>{{number_format($monster->gold_cost)}}</dd>
                <dt>Gold Dust Cost:</dt>
                <dd>{{number_format($monster->gold_dust_cost)}}</dd>
                <dt>Shard Reward:</dt>
                <dd>{{number_format($monster->shards)}}</dd>
            </dl>
        </div>
    </div>
@endif
