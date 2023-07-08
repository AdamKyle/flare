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
            title="{{$itemAffix->name}} ({{$itemAffix->type}})"
            buttons="true"
            backUrl="{{$backUrl}}"
            editUrl="{{route('affixes.edit', ['affix' => $itemAffix->id])}}"
        >
            <p class="mt-4 mb-4">{{$itemAffix->description}}</p>
            <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
            <div class='grid md:grid-cols-3 gap-2'>
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
                    <h3 class="text-sky-600 dark:text-sky-500">Enemy Stat Reduction</h3>
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
                </div>
            </div>
            <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
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
                <x-core.alerts.warning-alert title="ATTN!">
                    <p class="my-2">
                        Some damage based affixes can stack - which is outlined below. Those that can stack are able to be resisted
                        by the enemy. 
                    </p>
                    <p>
                        Those that do not stack, are irresistible and cannot be blocked.
                    </p>
                </x-core.alerts.warning-alert>
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
        @if (!is_null($itemAffix->steal_life_amount))
            <x-core.cards.card-with-title
                title="Life Stealing"
                buttons="false"
            >
                <x-core.alerts.warning-alert title="ATTN!">
                    These affixes will only stack, both stat increase and life stealing, for Vampire classes.
                    For other classes, we will take the Lifestealing Affix attached. The durability will 
                    stack when calculating your durability and therfor your health - for all classes.
                </x-core.alerts.warning-alert>
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
                <x-core.alerts.warning-alert title="ATTN!">
                    These Affixes will not stack. Entrancing the enemy makes it so your attack cannot be blocked and will not miss. The higher the chance,
                    the more possibility you have of entrancing the enemy.
                </x-core.alerts.warning-alert>
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
                <x-core.alerts.warning-alert title="ATTN!">
                    These Affixes will not stack. Devouring light allows you to void the enemy, which prevents them from using enchantments, life stealing and - more importantly - from voiding you,
                    which essentially weakens you as your enchantments become useless.
                </x-core.alerts.warning-alert>
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
                <x-core.alerts.warning-alert title="ATTN!">
                    These Affixes will not stack. These can reduce the base skills of en 
                    enemy such as Acuracy, needed to hit you or Dodge - used to get out the way of your attack.
                </x-core.alerts.warning-alert>
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
                <x-core.alerts.warning-alert title="ATTN!">
                    <p class='my-2'>These Affixes will not stack. The following resistances are reduced makig it easier for specific abilities to land:</p>
                    <ul class="mb-4 list-disc ml-[20px]">
                        <li>Spell Evasion</li>
                        <li>Affix Resistance</li>
                        <li>Ambush Resistance</li>
                        <li>Counter Resistance</li>
                    </ul>
                </x-core.alerts.warning-alert>
                <dl>
                    <dt>Resistance Reduction:</dt>
                    <dd>{{$itemAffix->resistance_reduction * 100}}%</dd>
                </dl>
            </x-core.cards.card-with-title>
        @endif
    </x-core.layout.info-container>
@endsection
