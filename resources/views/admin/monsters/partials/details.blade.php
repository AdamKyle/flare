<x-core.cards.card>
    <div class="grid md:grid-cols-2 gap-4">
        <div>
            <strong>Stats</strong>
            <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
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
            <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
            <strong>Skills</strong>
            <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
            <dl>
                <dt>Accuracy</dt>
                <dd>{{$monster->accuracy * 100}}%</dd>
                <dt>Casting Accuracy</dt>
                <dd>{{$monster->casting_accuracy * 100}}%</dd>
                <dt>Criticality</dt>
                <dd>{{$monster->criticality * 100}}%</dd>
                <dt>Dodge</dt>
                <dd>{{$monster->dodge * 100}}%</dd>
            </dl>
        </div>
        <div class='block md:hidden border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
        <div>
            <strong>Health/Damage/AC</strong>
            <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
            <dl>
                <dt>Health Range</dt>
                <dd>{{number_format(explode('-', $monster->health_range)[0])}} - {{number_format(explode('-', $monster->health_range)[1])}}</dd>
                <dt>Attack Range</dt>
                <dd>{{number_format(explode('-', $monster->attack_range)[0])}} - {{number_format(explode('-', $monster->attack_range)[1])}}</dd>
                <dt>AC</dt>
                <dd>{{number_format($monster->ac)}}</dd>
            </dl>
            <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
            <strong>Reward Details</strong>
            <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
            <dl>
                <dt>Drop Chance</dt>
                <dd>{{$monster->drop_check * 100}}%</dd>
                <dt>XP</dt>
                <dd>{{$monster->xp}}</dd>
                <dt>Max Level<sup>*</sup></dt>
                <dd>{{$monster->max_level}}</dd>
                <dt>Gold Reward</dt>
                <dd>{{number_format($monster->gold)}}</dd>
            </dl>
            <p class="mt-4"><sup>*</sup> Indicates that if you are over this level, you only get 1/3<sup>rd</sup> the monster's XP</p>
        </div>
    </div>
    <div class="grid md:grid-cols-2 gap-4">
        <div>
            <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
            <strong>Resistances</strong>
            <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
            <x-core.alerts.info-alert title="Info">
                <p>All Enemies have a chance to completely annul your affixes that do damage. This percentage is also known as
                    a "chance". The stronger the enemy the higher the chance to annul your affix damage.</p>
                <p>
                    There are some affixes who's damage cannot be resisted, this is known as irresistible damage. Even if an enemy's
                    Affix Resistance is over 100% this damage cannot be resisted.
                </p>
                <p>
                    There is a quest item that can make your affixes irresistible, which can then be upgraded to make your rings and
                    artifacts irresistible. You can then further upgrade this to make spells irresistible.
                </p>
                <ul>
                    <li>Casters will use 5% of their Focus as the base DC check for enemy's resistances check to see if the enemy can avoid the spell(s).</li>
                    <li>Vampires will use 5% of their Durability as the base dc check for resistances check to see if the enemy can avoid life stealing affixes.</li>
                </ul>
            </x-core.alerts.info-alert>
            <dl>
                <dt>Affix Resistance (chance):</dt>
                <dd>{{$monster->affix_resistance * 100}}%</dd>
                <dt>Artifact Annulment (chance):</dt>
                <dd>{{$monster->artifact_annulment * 100}}%</dd>
                <dt>Spell Evasion (chance):</dt>
                <dd>{{$monster->spell_evasion * 100}}%</dd>
            </dl>
        </div>
        <div class='block md:hidden border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
        <div>
            <x-core.alerts.info-alert title="Devouring Light and Devouring Darkness">
                <p class="mb-4">
                    Some monsters can void you. Much like the quest item to obtain for voiding enemies of their affixes, spells,
                    artifacts and ability to heal - a Celestial can void your affixes. This is called: Devouring Light.
                    All Celestials have a Devouring Light Percentage starting at or above 50%. Harder critters in the list start at 10% and go up.
                </p>
                <p class="mb-4">Special locations can increase this (and everything else - except drop chance) by 25% in addition to any map increases in enemy strength.</p>
                <p class="mb-4">There is a special quest item you can obtain to "devoid" their void, also known as: Devouring Darkness.
                    This has a chance starting at 50% to void out their chance to void you. You can upgrade this item to upgrade its chance.</p>
                <p class="mb-4">
                    Monsters in purgatory have a Devouring Darkness chance, which means they can devoid you - you cannot void them. To get around this
                    players will craft <a href="/information/holy-items">Holy Items</a> after completing a quest line. If a player becomes devoided,
                    their void (Devouring light) will not fire, much like for enemies who you devoid.
                </p>
            </x-core.alerts.info-alert>
            <dl>
                <dt>Devouring Light Chance:</dt>
                <dd>{{$monster->devouring_light_chance * 100}}%</dd>
                <dt>Devouring Darkness Chance:</dt>
                <dd>{{$monster->devouring_darkness_chance * 100}}%</dd>
            </dl>
        </div>
    </div>

</x-core.cards.card>

<x-core.cards.card-with-title title="Cast and Affixes">
    <x-core.alerts.info-alert title="Cating Help">
        <p class="mb-4">
            All monsters can cast to some degree, affixes and can heal. Here you will see the details corresponding to that.
            There are a couple of things to keep in mind, however:
        </p>
        <ul class="mb-4 list-disc ml-[20px]">
            <li>
                Monsters cast on their turn, same for affixes and artifacts.
            </li>
            <li>
                Monsters follow the same rules as players, if you are blocked (or miss), your rings and artifacts and affixes fire.
            </li>
            <li>
                Monsters will only heal, if they get a turn. In that case, like players, they heal at the end of their turn for a % of their Dur.
                This is where stat reducing affixes can come in handy. You can reduce the enemy's durability so they cannot heal as much.
            </li>
        </ul>
    </x-core.alerts.info-alert>
    <dl class="mt-3">
        <dt>Max Cast For</dt>
        <dd>{{number_format($monster->max_spell_damage)}}</dd>
        <dt>Max Artifact Damage</dt>
        <dd>{{number_format($monster->max_artifact_damage)}}</dd>
        <dt>Max Affix Damage</dt>
        <dd>{{number_format($monster->max_artifact_damage)}}</dd>
        <dt>Healing Percentage</dt>
        <dd>{{$monster->healing_percentage * 100}}%</dd>
        <dt>Entrancing Chance</dt>
        <dd>{{$monster->entrancing_chance * 100}}%</dd>
    </dl>
</x-core.cards.card-with-title>
<hr />
<x-core.cards.card-with-title title="Ambush & Counter">
    <x-core.alerts.info-alert title="Ambush and Counter Info">
        <p class="mb-4">
            Some monsters, specifically those who live in Purgatory, will have what is known as Ambush and Counter Stats.
            Ambush allows the enemy to attempt to get the jump on you doing 2x their normal weapon attack. Counter allows the enemy to counter
            your attack at +50% to their weapon attack.
        </p>
        <p class="mb-4">
            Players can also get Ambush and Counter stats on gear such as Trinkets which can be crafted only when you have access to Purgatory and copper coins.
            For more info, it is suggested players read the <a href="/information/combat">Combat docs</a> and <a href="/information/crafting">Crafting guide</a> as
            Trinkets can only be crafted.
        </p>
    </x-core.alerts.info-alert>
    <dl class="mt-3">
        <dt>Ambush Chance</dt>
        <dd>{{$monster->ambush_chance * 100}}%</dd>
        <dt>Ambush Resistance Chance</dt>
        <dd>{{$monster->ambush_resistance * 100}}%</dd>
        <dt>Counter Chance</dt>
        <dd>{{$monster->counter_chance * 100}}%</dd>
        <dt>Counter Resistance Chance</dt>
        <dd>{{$monster->counter_resistance * 100}}%</dd>
    </dl>
</x-core.cards.card-with-title>

@if ($monster->is_celestial_entity)
    <hr />
    <x-core.cards.card-with-title title="Celestial Conjuration Cost">
        <x-core.alerts.info-alert title="Conjuration Help">
            <p class="mb-4">This is a celestial entity which can only be conjured via a special NPC. You can learn more about those <a href="/information/celestials">here</a>.</p>
            <p class="mb-4">This creature will have a cost in <strong>Gold</strong> and <strong>Gold Dust</strong> and can be summoned either privately or publicly.</p>
            <p class="mb-4">These Creatures can also give quest rewards when defeated, as well as other items.</p>
            <p class="mb-4">Celestial entities also drop what are called <strong>Shards</strong>. These are used in <a href="/information/usable-items">Alchemy</a> in place of gold.</p>
            <p class="mb-4">Celestial Entities can also spawn randomly on the map by a player, any player, just moving around. When these entities spawn - be it summoned or other wise, they
                spawn in random locations at which the player must then go to. If the location is a kingdom is a small chance of it doing damage to the kingdom. The chance of a beast spawning
                is greater than the chance of it doing damage to a kingdom when it does spawn.</p>
            <p class="mb-4">
                <strong>Vampires</strong> will only do 50% damage to these creatures via their life stealing affixes and class bonus.
            </p>
        </x-core.alerts.info-alert>
        <dl class="mt-3">
            <dt>Gold Cost:</dt>
            <dd>{{number_format($monster->gold_cost)}}</dd>
            <dt>Gold Dust Cost:</dt>
            <dd>{{number_format($monster->gold_dust_cost)}}</dd>
            <dt>Shard Reward:</dt>
            <dd>{{number_format($monster->shards)}}</dd>
        </dl>
    </x-core.cards.card-with-title>
@endif
