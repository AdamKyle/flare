<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <dl>
                    <dt>str</dt>
                    <dd>{{number_format($monster->str)}}</dd>
                    <dt>dex</dt>
                    <dd>{{number_format($monster->dex)}}</dd>
                    <dt>dur</dt>
                    <dd>{{number_format($monster->dur)}}</dd>
                    <dt>chr</dt>
                    <dd>{{number_format($monster->chr)}}</dd>
                    <dt>int</dt>
                    <dd>{{number_format($monster->int)}}</dd>
                    <dt>agi</dt>
                    <dd>{{number_format($monster->int)}}</dd>
                    <dt>focus</dt>
                    <dd>{{number_format($monster->int)}}</dd>
                    <dt>Damage Stat</dt>
                    <dd>{{$monster->damage_stat}}</dd>
                </dl>
            </div>
            <div class="col-md-4">
                <dl>
                    <dt>Health Range</dt>
                    <dd>{{$monster->health_range}}</dd>
                    <dt>Attack Range</dt>
                    <dd>{{$monster->attack_range}}</dd>
                    <dt>Drop Check</dt>
                    <dd>{{$monster->drop_check * 100}}%</dd>
                    <dt>AC</dt>
                    <dd>{{number_format($monster->ac)}}</dd>
                    <dt>XP</dt>
                    <dd>{{$monster->xp}}</dd>
                    <dt>Max Level<sup>*</sup></dt>
                    <dd>{{$monster->max_level}}</dd>
                    <dt>Gold Reward</dt>
                    <dd>{{number_format($monster->gold)}}</dd>
                </dl>
            </div>
            <div class="col-md-4">
                <dl>
                    <dt>Spell Evasion</dt>
                    <dd>{{$monster->spell_evasion * 100}}%</dd>
                    <dt>Artifact Annulment</dt>
                    <dd>{{$monster->artifact_annulment * 100}}%</dd>
                </dl>
            </div>
            <p class="ml-3 mt-3">
                <span class="text-muted" style="font-size: 12px;"><sup>*</sup> Once a character is at this level or above it, they get 1/3rd the xp</span>
            </p>
        </div>
        @if ($monster->skills->isNotEmpty())
            <hr />
            <h4>Skills</h4>
            <div class="row">
                @php
                    $colSize = 12 / $monster->skills->count();
                @endphp
                @foreach($monster->skills as $skill)
                    <div class="col-xs-12 col-sm-{{$colSize}}">
                        <dl>
                            <dt>Name</dt>
                            <dd>{{$skill->name}}</dd>
                            <dt>Level</dt>
                            <dd>{{$skill->level}}</dd>
                            <dt>Bonus</dt>
                            <dd>{{$skill->skill_bonus * 100}}%</dd>
                        </dl>
                    </div>
                @endforeach
            </div>
        @endif
        @guest
        @elseif (auth()->user()->hasRole('Admin') && $canEdit)
            <a href="{{route('monster.edit', [
                'monster' => $monster->id,
            ])}}" class="btn btn-primary mt-2">Edit Monster</a>
        @endguest
    </div>
</div>

<hr />
<h4 class="mt-2">Resistances</h4>
<div class="card">
    <div class="card-body">
        <div class="alert alert-info mt-2">
            <p>All Enemies have a chance to completely annul your affixes that do damage. This percentage is also known as
                a "chance". The stronger the enemy the higher the chance to annul your affix damage.</p>
            <p>
                There are some affixes who's damage cannot be resisted, this is known as irresistible damage. Even if an enemies
                Affix Resistance is over 100% this damage cannot be resisted.
            </p>
        </div>
        <dl class="mt-3">
            <dt>Affix Resistance (chance):</dt>
            <dd>{{$monster->affix_resistance * 100}}%</dd>
        </dl>
    </div>
</div>

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
