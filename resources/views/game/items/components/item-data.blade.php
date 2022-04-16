@php
    $backUrl = route('items.list');

    if (!auth()->user()->hasRole('admin')) {
        $backUrl = route('game.shop.buy', ['character' => auth()->user()->character->id]);
    }
@endphp

<x-core.cards.card-with-title
    title="{{$item->name}}"
    buttons="true"
    backUrl="{{$backUrl}}"
    editUrl="{{route('items.edit', ['item' => $item->id])}}"
>
    <div class="grid md:grid-cols-3 gap-3">
        <div>
            <strong>Stats</strong>
            <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
            <dl>
                <dt>Str Modifier</dt>
                <dd>{{$item->str_mod * 100}} %</dd>
                <dt>Dex Modifier</dt>
                <dd>{{$item->dex_mod * 100}} %</dd>
                <dt>Agi Modifier</dt>
                <dd>{{$item->agi_mod * 100}} %</dd>
                <dt>Chr Modifier</dt>
                <dd>{{$item->chr_mod * 100}} %</dd>
                <dt>Dur Modifier</dt>
                <dd>{{$item->dur_mod * 100}} %</dd>
                <dt>Int Modifier</dt>
                <dd>{{$item->int_mod * 100}} %</dd>
                <dt>Focus Modifier</dt>
                <dd>{{$item->focus_mod * 100}} %</dd>
            </dl>
        </div>
        <div class='block md:hidden border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
        <div>
            <strong>Modifiers</strong>
            <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
            <dl>
                <dt>Base Damage</dt>
                <dd>{{$item->base_damage > 0 ? $item->base_damage : 0}}</dd>
                <dt>Base Ac</dt>
                <dd>{{$item->base_ac > 0 ? $item->base_ac : 0}}</dd>
                <dt>Base Healing</dt>
                <dd>{{$item->base_healing > 0 ? $item->base_healing : 0}}</dd>
                <dt>Base Damage Mod</dt>
                <dd>{{$item->base_damage_mod * 100}} %</dd>
                <dt>Base Ac Mod</dt>
                <dd>{{$item->base_ac_mod * 100}} %</dd>
                <dt>Base Healing Mod</dt>
                <dd>{{$item->base_healing_mod * 100}} %</dd>
            </dl>
        </div>
        <div class='block md:hidden border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
        <div>
            <strong>Evasion and Reductions</strong>
            <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
            <dl>
                <dt>Spell Evasion</dt>
                <dd>{{$item->spell_evasion * 100}} %</dd>
                <dt>Artifact Dmg. Reduction</dt>
                <dd>{{$item->artifact_annulment * 100}} %</dd>
                <dt>Healing Reduction</dt>
                <dd>{{$item->healing_reduction * 100}} %</dd>
                <dt>Affix Dmg. Reduction</dt>
                <dd>{{$item->affix_damage_reduction * 100}} %</dd>
            </dl>
        </div>
    </div>
</x-core.cards.card-with-title>

<x-core.cards.card css="mt-4 mb-4">
    <div class="grid md:grid-cols-3 gap-3">
        <div>
            <strong>Devouring Chance</strong>
            <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
            <dl>
                <dt>Devouring Light</dt>
                <dd>{{$item->devouring_light * 100}} %</dd>
                <dt>Devouring Darkness</dt>
                <dd>{{$item->devouring_darkness * 100}} %</dd>
            </dl>
        </div>
        <div class='block md:hidden border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
        <div>
            <strong>Resurrection</strong>
            <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
            <dl>
                <dt>Chance</dt>
                <dd>{{$item->resurrection_chance * 100}} %</dd>
            </dl>
        </div>
        <div class='block md:hidden border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
        <div>
            <strong>Holy Info</strong>
            <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
            <dl>
                <dt>Holy Stacks</dt>
                <dd>{{$item->holy_stacks}}</dd>
            </dl>
        </div>
    </div>
</x-core.cards.card>

<x-core.cards.card css="mb-4">
    <div class="grid md:grid-cols-2 gap-3">
        <div>
            <strong>Ambush Info</strong>
            <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
            <dl>
                <dt>Chance</dt>
                <dd>{{$item->ambush_chance * 100}} %</dd>
                <dt>Resistance</dt>
                <dd>{{$item->ambush_resistance * 100}} %</dd>
            </dl>
        </div>
        <div class='block md:hidden border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
        <div>
            <strong>Counter</strong>
            <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
            <dl>
                <dt>Chance</dt>
                <dd>{{$item->counter_chance * 100}} %</dd>
                <dt>Resistance</dt>
                <dd>{{$item->counter_resistance * 100}} %</dd>
            </dl>
        </div>
    </div>
</x-core.cards.card>
