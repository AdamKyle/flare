@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        @php
            $backUrl = route('affixes.list');

            if (is_null(auth()->user())) {
                $backUrl = '/information/enchanting';
            } else if (!auth()->user()->hasRole('Admin')) {
                $backUrl = '/information/enchanting';
            }
        @endphp

        <x-core.cards.card-with-title
            title="{{$itemAffix->name}}"
            buttons="true"
            backUrl="{{$backUrl}}"
            editUrl="{{route('affixes.edit', ['affix' => $itemAffix->id])}}"
        >
            <p class="mt-4 mb-4">{{$itemAffix->description}}</p>
            <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
            <div class='grid md:grid-cols-2 gap-2'>
                <div>
                    <h3 class="text-sky-600 dark:text-sky-500">Stat Modifiers</h3>
                    <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                    <dl>
                        <dt>Str Modifier:</dt>
                        <dd>{{$itemAffix->str_mod * 100}}%</dd>
                        <dt>Dex Modifier:</dt>
                        <dd>{{$itemAffix->dex_mod * 100}}%</dd>
                        <dt>Dur Modifier:</dt>
                        <dd>{{$itemAffix->dur_mod * 100}}%</dd>
                        <dt>Int Modifier:</dt>
                        <dd >{{$itemAffix->int_mod * 100}}%</dd>
                        <dt>Chr Modifier:</dt>
                        <dd >{{$itemAffix->chr_mod * 100}}%</dd>
                        <dt>Agi Modifier:</dt>
                        <dd>{{$itemAffix->agi_mod * 100}}%</dd>
                        <dt>Focus Modifier:</dt>
                        <dd>{{$itemAffix->focus_mod * 100}}%</dd>
                    </dl>
                </div>
                <div class='block md:hidden border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                <div>
                    <h3 class="text-sky-600 dark:text-sky-500">Damage/AC/Healing Modifiers</h3>
                    <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                    <dl>
                        <dt>Base Attack Modifier:</dt>
                        <dd>{{$itemAffix->base_damage_mod * 100}}%</dd>
                        <dt>Base AC Modifier:</dt>
                        <dd>{{$itemAffix->base_ac_mod * 100}}%</dd>
                        <dt>Base Healing Modifier:</dt>
                        <dd>{{$itemAffix->base_healing_mod * 100}}%</dd>
                    </dl>
                    <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                    <h3 class="text-sky-600 dark:text-sky-500">Class Modifier</h3>
                    <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                    <dl>
                        <dt>Class Bonus Mod:</dt>
                        <dd>{{$itemAffix->class_bonus * 100}}%</dd>
                    </dl>
                </div>
            </div>
            <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
            <div class='grid md:grid-cols-2 gap-2'>
                <div>
                    <h3 class="text-sky-600 dark:text-sky-500">Skill Modifiers</h3>
                    <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                    <dl>
                        <dt>Skill Name:</dt>
                        <dd>{{is_null($itemAffix->skill_name) ? 'N/A' : $itemAffix->skill_name}}</dd>
                        <dt>Skill XP Bonus (When Training):</dt>
                        <dd>{{is_null($itemAffix->skill_name) ? 0 : $itemAffix->skill_training_bonus * 100}}%</dd>
                        <dt>Skill Bonus (When using)</dt>
                        <dd>{{is_null($itemAffix->skill_bonus) ? 0 : $itemAffix->skill_bonus * 100}}%</dd>
                    </dl>
                </div>
                <div class='block md:hidden border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                <div>
                    <h3 class="text-sky-600 dark:text-sky-500">Other Skill Modifiers</h3>
                    <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                    <dl>
                        <dt>Damage Modifier:</dt>
                        <dd>{{$itemAffix->base_damage_mod_bonus * 100}}%</dd>
                        <dt>AC Modifier:</dt>
                        <dd>{{$itemAffix->base_ac_mod_bonus * 100}}%</dd>
                        <dt>Healing Modifier:</dt>
                        <dd>{{$itemAffix->base_healing_mod_bonus * 100}}%</dd>
                        <dt>Fight Timeout Modifier:</dt>
                        <dd>{{$itemAffix->fight_time_out_mod_bonus * 100}}%</dd>
                        <dt>Move Timeout Modifier:</dt>
                        <dd>{{$itemAffix->move_time_out_mod_bonus * 100}}%</dd>
                    </dl>
                </div>
            </div>
        </x-core.cards.card-with-title>
        <x-core.cards.card-with-title
            title="Enchanting Info"
            buttons="false"
        >
            <dl>
                <dt>Base Cost:</dt>
                <dd>{{number_format($itemAffix->cost)}} Gold</dd>
                <dt>Intelligence Required:</dt>
                <dd>{{number_format($itemAffix->int_required)}}</dd>
                <dt>Level Required:</dt>
                <dd>{{$itemAffix->skill_level_required}}</dd>
                <dt>Level Trivial:</dt>
                <dd>{{$itemAffix->skill_level_trivial}}</dd>
            </dl>
        </x-core.cards.card-with-title>
        @if($itemAffix->damage !== 0)
            <x-core.cards.card-with-title
                title="Damage Info"
                buttons="false"
            >
                <p class="mt-4 mb-4">
                    Affixes such as these will fire automatically. However, enemies can outright
                    resist the damage done. All enemies have a % of resistance against affixes. Celestials have a higher
                    amount of resistance than regular dropdown critters.
                </p>
                <p class="mb-4">
                    Unlike artifact Annulment and Spell Evasion, the resistance will not reduce damage done, instead it will
                    out right nullify the damage. If the enchantment is marked as irresistible damage, then the enemy cannot resist
                    the incoming damage.
                </p>
                <p class="mb-4">
                    These affixes will fire, regardless if you miss or hit. These affixes cannot stack unless otherwise stated.
                    That means, having multiple will do nothing, you'll take the highest of all non stacking damaging affixes.
                </p>
                <p class="mb-4">
                    With the right quest item, you can make all damage from all affixes Irresistible.
                </p>
                <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                <dl>
                    <dt>Damage:</dt>
                    <dd>{{number_format($itemAffix->damage)}}</dd>
                    <dt>Is Damage Irresistible?:</dt>
                    <dd>{{$itemAffix->irresistible_damage ? 'Yes' : 'No'}}</dd>
                    <dt>Can Stack:</dt>
                    <dd>{{$itemAffix->damage_can_stack ? 'Yes' : 'No'}}</dd>
                </dl>
            </x-core.cards.card-with-title>
        @endif
        @if ($itemAffix->reduces_enemy_stats)
            <x-core.cards.card-with-title
                title="Enemy Stat Reduction"
                buttons="false"
            >
                <p class="mt-4 mb-4">
                    Affixes that reduce stats can and cannot stack. For example: Prefixes cannot stack, but Suffixes can.
                </p>
                <p class="mb-4">
                    If you have multiple prefixes attached that reduce all enemy stats, we will take the first one. Doesn't matter
                    what it is.
                </p>
                <p class="mb-4">
                    Stat reduction is applied before anything else is done, but can be resisted unless you have the appropriate quest item.
                </p>
                <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                <dl>
                    <dt>Str Reduction:</dt>
                    <dd>{{$itemAffix->str_reduction * 100}}%</dd>
                    <dt>Dex Reduction:</dt>
                    <dd>{{$itemAffix->dex_reduction * 100}}%</dd>
                    <dt>Dur Reduction:</dt>
                    <dd>{{$itemAffix->dur_reduction * 100}}%</dd>
                    <dt>Int Reduction:</dt>
                    <dd>{{$itemAffix->int_reduction * 100}}%</dd>
                    <dt>Chr Reduction:</dt>
                    <dd>{{$itemAffix->chr_reduction * 100}}%</dd>
                    <dt>Agi Reduction:</dt>
                    <dd>{{$itemAffix->agi_reduction * 100}}%</dd>
                    <dt>Focus Reduction:</dt>
                    <dd>{{$itemAffix->focus_reduction * 100}}%</dd>
                </dl>
            </x-core.cards.card-with-title>.
        @endif
        @if (!is_null($itemAffix->steal_life_amount))
            <x-core.cards.card-with-title
                title="Life Stealing"
                buttons="false"
            >
                <p class="mt-4 mb-4">
                    These Affixes can and cannot stack. If you are a vampire they will stack and you have a chance for them to fire twice.
                    The first time they can fire is during the attack and the second time is after the enemy's round if you or
                    the enemy is still alive.
                </p>
                <p class="mb-4">
                    If you are <strong>not</strong> a vampire, these affixes will
                    <strong>NOT</strong> stack. Instead, we will use your highest, and it will only fire after the enemy attack.
                </p>
                <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                <dl>
                    <dt>Steal Life Amount:</dt>
                    <dd>{{$itemAffix->steal_life_amount * 100}}%</dd>
                </dl>
            </x-core.cards.card-with-title>
        @endif
        @if ($itemAffix->entranced_chance > 0)
            <x-core.cards.card-with-title
                title="Entrance Chance"
                buttons="false"
            >
                <p class="mt-4 mb-4">
                    These Affixes do not stack. You have percentage chance to entrance the enemy so they cannot block or be missed.
                </p>
                <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                <dl>
                    <dt>Entrance Chance:</dt>
                    <dd>{{$itemAffix->entranced_chance * 100}}%</dd>
                </dl>
            </x-core.cards.card-with-title>
        @endif
        @if ($itemAffix->devouring_light > 0)
            <x-core.cards.card-with-title
                title="Devouring Light"
                buttons="false"
            >
                <p class="mt-4 mb-4">
                    These Affixes do not stack. You have a percentage chance to void the enemy of using their affixes. Some higher level critters
                    have a small chance to void you, while Celestials have a much higher chance. If you are voided, you lose all enchantments, no life stealing,
                    no modded stats and no boons.
                </p>
                <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                <dl>
                    <dt>Devouring Light Chance:</dt>
                    <dd>{{$itemAffix->devouring_light * 100}}%</dd>
                </dl>
            </x-core.cards.card-with-title>
        @endif
        @if ($itemAffix->skill_reduction > 0)
            <x-core.cards.card-with-title
                title="Enemy Skill Reduction"
                buttons="false"
            >
                <p class="mt-4 mb-4">
                    These Affixes only affect enemies and can reduce ALL their skills at once by a specified %. These affixes work
                    in the same vein as stat reduction affixes, however these do not stack. We take the best one of all you have on.
                </p>
                <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                <dl>
                    <dt>Skills Affected:</dt>
                    <dd>Accuracy, Dodge, Casting Accuracy and Criticality</dd>
                    <dt>Skills Reduced By:</dt>
                    <dd>{{$itemAffix->skill_reduction * 100}}%</dd>
                </dl>
            </x-core.cards.card-with-title>
        @endif
        @if ($itemAffix->resistance_reduction > 0)
            <x-core.cards.card-with-title
                title="Enemy Resistance Reduction"
                buttons="false"
            >
                <p class="mt-4 mb-4">These affixes do not stack and only effect the enemy. These reduce the following resistances that all enemies have:</p>
                <ul class="mb-4 list-disc ml-[20px]">
                    <li>Spell Evasion</li>
                    <li>Affix Resistance</li>
                </ul>
                <p class="mb-4">Should you have many equipped, we will take the best one of them all.</p>
                <p class="mb-4">Much like skill reduction and stat reduction these are applied only if you are not voided and before the fight begins.</p>
                <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                <dl>
                    <dt>Resistance Reduction:</dt>
                    <dd>{{$itemAffix->resistance_reduction * 100}}%</dd>
                </dl>
            </x-core.cards.card-with-title>
        @endif
    </x-core.layout.info-container>
@endsection
