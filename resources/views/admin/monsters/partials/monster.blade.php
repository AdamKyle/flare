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
    'quest'   => $quest,
    'canEdit' => true,
])

@if ($monster->can_cast)
    <hr />
    <h4 class="mt-2">Evasion</h4>
    <div class="card">
        <div class="card-body">
            <div class="alert alert-info mt-2">
                Spell Evasion will reduce the players spell damage, while Artifact annulment will reduce the characters artifact damage.
                Players can equip rings to increase their own spell evasion and artifact annulment to reduce the enemies spells and artifact damage.
            </div>
            <dl class="mt-3">
                <dt>Max Cast For</dt>
                <dd>{{number_format($monster->max_spell_damage)}}</dd>
                <dt>Max Artifact Damage</dt>
                <dd>{{number_format($monster->max_artifact_damage)}}</dd>
            </dl>
        </div>
    </div>
@endif

@if ($monster->is_celestial_entity)
    <hr />
    <h4 class="mt-2">Celestial Conjuration Cost</h4>
    <div class="card">
        <div class="card-body">
            <div class="alert alert-info mb-2 mt-2">
                <p>This is a celestial entity which can only be conjured via a special NPC. You can learn more about those <a href="#">here</a>.</p>
                <p>This creature will have a cost in <strong>Gold</strong> and <strong>Gold Dust</strong> and can be summoned either privately or publicly.</p>
                <p>These Creatures can also give quest rewards when defeated, as well as other items.</p>
                <p>Celestial entities also drop what are called <strong>Shards</strong>. These are used in <a href="#">Alchemy</a> in place of gold.</p>
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
