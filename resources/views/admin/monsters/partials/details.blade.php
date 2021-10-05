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
            <p>
                There is a quest item that can make your affixes irresistible, which can then be upgraded to make your rings and
                artifacts irresistible. You can then further upgrade this to make spells irresistible.
            </p>
            <ul>
                <li>Casters will use 5% of their Focus as the base DC check for enemies resistances check to see if the enemy can avoid the spell(s).</li>
                <li>Vampires will use 5% of their Durability as the base dc check for resistances check to see if the enemy can avoid life stealing affixes.</li>
            </ul>
        </div>
        <dl class="mt-3">
            <dt>Affix Resistance (chance):</dt>
            <dd>{{$monster->affix_resistance * 100}}%</dd>
            <dt>Artifact Annulment (chance):</dt>
            <dd>{{$monster->artifact_annulment * 100}}%</dd>
            <dt>Spell Evasion (chance):</dt>
            <dd>{{$monster->spell_evasion * 100}}%</dd>
        </dl>
    </div>
</div>

<hr />
<h4 class="mt-2">Cast, Artifacts and Affixes </h4>
<div class="card">
    <div class="card-body">
        <div class="alert alert-info mt-2">
            <p>
                All monsters can cast to some degree, have artifacts, affixes and can heal. Here you will see the details corresponding to that.
                There are a couple things to keep in mind however:
            </p>
            <ul>
                <li>
                    Monsters cast on their turn, same for affixes and artifacts.
                </li>
                <li>
                    Monsters follow the same rules as players, if you are blocked (or miss), your rings and artifacts and affixes fire.
                </li>
                <li>
                    Monsters will only heal, if they get a turn. In that case, like players, they heal at the end of their turn for a % of their Dur.
                    This is where stat reducing affixes can come in handy. You can reduce the enemies durability so they cannot heal as much.
                </li>
            </ul>
            <p>
                There is a quest chain you can do that rewards you at the end with a new item that will annul the enemies affixes, artifacts, spells
                and ability to heal.
            </p>
        </div>
        <dl class="mt-3">
            <dt>Max Cast For</dt>
            <dd>{{number_format($monster->max_spell_damage)}}</dd>
            <dt>Max Artifact Damage</dt>
            <dd>{{number_format($monster->max_artifact_damage)}}</dd>
            <dt>Max Affix Damage</dt>
            <dd>{{number_format($monster->max_artifact_damage)}}</dd>
            <dt>Healing Percentage</dt>
            <dd>{{$monster->healing_percentage * 100}}%</dd>
        </dl>
    </div>
</div>

@if ($monster->is_celestial_entity)
    <hr />
    <h4 class="mt-2">Celestial Conjuration Cost</h4>
    <div class="card">
        <div class="card-body">
            <div class="alert alert-info mb-2 mt-2">
                <p>This is a celestial entity which can only be conjured via a special NPC. You can learn more about those <a href="/information/celestials">here</a>.</p>
                <p>This creature will have a cost in <strong>Gold</strong> and <strong>Gold Dust</strong> and can be summoned either privately or publicly.</p>
                <p>These Creatures can also give quest rewards when defeated, as well as other items.</p>
                <p>Celestial entities also drop what are called <strong>Shards</strong>. These are used in <a href="/information/usable-items">Alchemy</a> in place of gold.</p>
                <p>Celestial Entities can also spawn randomly on the map by a player, any player, just moving around. When these entities spawn - be it summoned or other wise, they
                    spawn in random locations at which the player must then go to. If the location is a kingdom is a small chance of it doing damage to the kingdom. The chance of a beast spawning
                    is greater then the chance of it doing damage to a kingdom when it does spawn.</p>
                <p>
                    <strong>Vampires</strong> will only do half damage to these creatures via their life stealing affixes.
                </p>
                <p>
                    Celestials can void you. Much like the quest item to obtain for voiding enemies of their affixes, spells,
                    artifacts and ability to heal - a Celestial can void your affixes and artifacts. This is called: Devouring Light.
                    All Celestials have a Devouring Light Percentage starting at or above 50%.
                </p>
                <p>There is a special quest item you can obtain to "devoid" their void, also known as: Devouring Darkness.
                    This has a chance starting at 50% to void out their chance to void you. You can upgrade this item to upgrade its chance.</p>
                <p>
                    If you fail to kill a celestial in one hit, it will have a "flee chance". Should it meet this chance, it will flee from battle
                    to a new location.
                </p>
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
