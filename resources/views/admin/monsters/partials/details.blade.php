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
            <dl>
                <dt>Affix Resistance (chance):</dt>
                <dd>{{$monster->affix_resistance * 100}}%</dd>
                <dt>Spell Evasion (chance):</dt>
                <dd>{{$monster->spell_evasion * 100}}%</dd>
            </dl>
        </div>
        <div class='block md:hidden border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
        <div>
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
    <dl class="mt-3">
        <dt>Max Cast For</dt>
        <dd>{{number_format($monster->max_spell_damage)}}</dd>
        <dt>Max Affix Damage</dt>
        <dd>{{number_format($monster->max_affix_damage)}}</dd>
        <dt>Healing Percentage</dt>
        <dd>{{$monster->healing_percentage * 100}}%</dd>
        <dt>Entrancing Chance</dt>
        <dd>{{$monster->entrancing_chance * 100}}%</dd>
    </dl>
</x-core.cards.card-with-title>
<hr />
<x-core.cards.card-with-title title="Ambush & Counter">
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
    <x-core.cards.card-with-title title="Celestial Conjuration Cost/Reward">
        <dl class="mt-3">
            @if ($monster->gold_cost > 0)
                <dt>Gold Cost:</dt>
                <dd>{{number_format($monster->gold_cost)}}</dd>
            @endif
            @if ($monster->gold_dust_cost > 0)
                <dt>Gold Dust Cost:</dt>
                <dd>{{number_format($monster->gold_dust_cost)}}</dd>
            @endif
            <dt>Shard Reward:</dt>
            <dd>{{number_format($monster->shards)}}</dd>
        </dl>
    </x-core.cards.card-with-title>
@endif
