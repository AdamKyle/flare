@php
    $atonementNames = \App\Game\Gems\Values\GemTypeValue::getNames();
    $craftingSkillNames = \App\Flare\Models\GameSkill::whereIn('id', $rolledGem->crafting_skill_ids ?? [])
        ->orderBy('name')
        ->pluck('name')
        ->implode(', ');
    $rolledSections = [
        'Player Rewards' => [
            'info_title' => 'Player reward bonuses',
            'info_content' => 'Actual rolled values that boost character progression, class rank XP, specialty XP, and reduce passive training time.',
            'fields' => [
                'character_xp_bonus' => ['Character XP Bonus', 'text-green-700 dark:text-green-400'],
                'character_class_rank_xp_bonus' => ['Character Class Rank XP Bonus', 'text-green-700 dark:text-green-400'],
                'character_class_specialty_xp_gain' => ['Character Class Specialty XP Gain', 'text-green-700 dark:text-green-400'],
                'kingdom_passive_training_reduction' => ['Kingdom Passive Training Reduction', 'text-green-700 dark:text-green-400'],
                'crafting_skill_bonus' => ['Crafting Skill Bonus', 'text-green-700 dark:text-green-400'],
            ],
        ],
        'Currency and Drops' => [
            'info_title' => 'Currency and item rewards',
            'info_content' => 'Actual rolled values that increase currency gains and item drop chances from enemies.',
            'fields' => [
                'gold_gain' => ['Gold Gain', 'text-green-700 dark:text-green-400'],
                'gold_dust_gain' => ['Gold Dust Gain', 'text-green-700 dark:text-green-400'],
                'shards_gain' => ['Shards Gain', 'text-green-700 dark:text-green-400'],
                'copper_coin_gain' => ['Copper Coin Gain', 'text-green-700 dark:text-green-400'],
                'item_drop_chance_increase' => ['Item Drop Chance Increase', 'text-green-700 dark:text-green-400'],
                'unique_item_drop_chance_increase' => ['Unique Item Drop Chance Increase', 'text-green-700 dark:text-green-400'],
                'mythic_item_drop_chance_increase' => ['Mythic Item Drop Chance Increase', 'text-green-700 dark:text-green-400'],
                'cosmic_item_drop_chance_increase' => ['Cosmic Item Drop Chance Increase', 'text-green-700 dark:text-green-400'],
                'ascended_item_drop_chance_increase' => ['Ascended Item Drop Chance Increase', 'text-green-700 dark:text-green-400'],
            ],
        ],
        'Enemy Combat' => [
            'info_title' => 'Enemy combat modifiers',
            'info_content' => 'Actual rolled values that increase enemy difficulty, healing, evasion, resistances, and special attack chances.',
            'fields' => [
                'enemy_strength_increase' => ['Enemy Strength Increase', 'text-red-700 dark:text-red-400'],
                'enemy_healing_increase' => ['Enemy Healing Increase', 'text-red-700 dark:text-red-400'],
                'enemy_spell_evasion' => ['Enemy Spell Evasion', 'text-red-700 dark:text-red-400'],
                'enemy_affix_resistance' => ['Enemy Affix Resistance', 'text-red-700 dark:text-red-400'],
                'enemy_entrancing_chance' => ['Enemy Entrancing Chance', 'text-red-700 dark:text-red-400'],
                'enemy_devouring_light_chance' => ['Enemy Devouring Light Chance', 'text-red-700 dark:text-red-400'],
                'enemy_devouring_darkness_chance' => ['Enemy Devouring Darkness Chance', 'text-red-700 dark:text-red-400'],
                'enemy_ambush_chance' => ['Enemy Ambush Chance', 'text-red-700 dark:text-red-400'],
                'enemy_ambush_resistance' => ['Enemy Ambush Resistance', 'text-red-700 dark:text-red-400'],
                'enemy_counter_chance' => ['Enemy Counter Chance', 'text-red-700 dark:text-red-400'],
                'enemy_counter_resistance' => ['Enemy Counter Resistance', 'text-red-700 dark:text-red-400'],
            ],
        ],
        'Monster Rewards' => [
            'info_title' => 'Monster reward modifiers',
            'info_content' => 'Actual rolled values that boost rewards from defeating monsters, including quest items, XP, and gold.',
            'fields' => [
                'enemy_quest_item_drop_chance_increase' => ['Enemy Quest Item Drop Chance Increase', 'text-green-700 dark:text-green-400'],
                'monster_xp_increase' => ['Monster XP Increase', 'text-green-700 dark:text-green-400'],
                'monster_gold_drop_increase' => ['Monster Gold Drop Increase', 'text-green-700 dark:text-green-400'],
                'faction_point_increase' => ['Faction Point Increase', 'text-green-700 dark:text-green-400'],
            ],
        ],
    ];

    if ($showCharacterPowerReduction) {
        $rolledSections['Enemy Combat']['fields'] = [
            'character_power_reduction' => ['Character Power Reduction', 'text-red-700 dark:text-red-400', true],
            ...$rolledSections['Enemy Combat']['fields'],
        ];
    }
@endphp

<div class="space-y-6 text-gray-700 dark:text-gray-300">
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(18rem,24rem)] lg:items-start">
        <section class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">Current Rolled Gem</h3>

            <dl class="grid grid-cols-1 gap-x-8 gap-y-4 lg:grid-cols-[minmax(10rem,16rem)_minmax(0,1fr)_minmax(10rem,16rem)_minmax(0,1fr)]">
                <dt class="text-sm font-semibold leading-6 text-gray-900 dark:text-gray-100">Name</dt>
                <dd class="text-sm leading-6 text-gray-700 dark:text-gray-300">{{ $rolledGem->name }}</dd>
                <dt class="text-sm font-semibold leading-6 text-gray-900 dark:text-gray-100">Gem Roll Number</dt>
                <dd class="text-sm leading-6 text-gray-700 dark:text-gray-300">{{ $rolledGem->roll_number }}</dd>
                <dt class="text-sm font-semibold leading-6 text-gray-900 dark:text-gray-100">Domain</dt>
                <dd class="text-sm leading-6 text-gray-700 dark:text-gray-300">{{ $rolledGem->domain }}</dd>
                @if(! is_null($rolledGem->monster_atonement) && isset($atonementNames[$rolledGem->monster_atonement]))
                    <dt class="text-sm font-semibold leading-6 text-gray-900 dark:text-gray-100">Monster Atonement</dt>
                    <dd class="text-sm leading-6 text-gray-700 dark:text-gray-300">{{ $atonementNames[$rolledGem->monster_atonement] }}</dd>
                @endif
                @if((float) $rolledGem->monster_atonement_amount > 0)
                    <dt class="text-sm font-semibold leading-6 text-gray-900 dark:text-gray-100">Monster Atonement Amount</dt>
                    <dd class="text-sm font-medium leading-6 text-green-700 dark:text-green-400">+{{ number_format((float) $rolledGem->monster_atonement_amount * 100, 3) }}%</dd>
                @endif
                @if($craftingSkillNames !== '')
                    <dt class="text-sm font-semibold leading-6 text-gray-900 dark:text-gray-100">Crafting Skills</dt>
                    <dd class="text-sm leading-6 text-gray-700 dark:text-gray-300">{{ $craftingSkillNames }}</dd>
                @endif
            </dl>
        </section>

        <aside class="self-start rounded-lg border border-blue-300 bg-blue-50 p-4 text-blue-900 dark:border-blue-700 dark:bg-blue-950 dark:text-blue-100">
            <h2 class="mb-2 text-lg font-semibold">About this rolled gem</h2>
            <p class="text-sm leading-6">Shows the name, roll number, domain, and any crafting skill or atonement bonuses for this specific gem result.</p>
        </aside>
    </div>

    @foreach($rolledSections as $sectionTitle => $section)
        @php
            $visibleRows = collect($section['fields'])
                ->map(function (array $fieldConfig, string $field) use ($rolledGem): ?array {
                    [$label, $colorClass, $isNegative] = array_pad($fieldConfig, 3, false);
                    if ((float) $rolledGem->{$field} <= 0) {
                        return null;
                    }
                    $prefix = $isNegative ? '-' : '+';
                    $displayValue = number_format((float) $rolledGem->{$field} * 100, 3);

                    return ['label' => $label, 'value' => $prefix.$displayValue.'%', 'class' => $colorClass];
                })
                ->filter();
        @endphp
        @if($visibleRows->isNotEmpty())
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(18rem,24rem)] lg:items-start">
                <section class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $sectionTitle }}</h3>

                    <dl class="grid grid-cols-1 gap-x-8 gap-y-4 lg:grid-cols-[minmax(10rem,16rem)_minmax(0,1fr)_minmax(10rem,16rem)_minmax(0,1fr)]">
                        @foreach($visibleRows as $row)
                            <dt class="text-sm font-semibold leading-6 text-gray-900 dark:text-gray-100">{{ $row['label'] }}</dt>
                            <dd class="text-sm font-medium leading-6 {{ $row['class'] }}">{{ $row['value'] }}</dd>
                        @endforeach
                    </dl>
                </section>

                <aside class="self-start rounded-lg border border-blue-300 bg-blue-50 p-4 text-blue-900 dark:border-blue-700 dark:bg-blue-950 dark:text-blue-100">
                    <h2 class="mb-2 text-lg font-semibold">{{ $section['info_title'] }}</h2>
                    <p class="text-sm leading-6">{{ $section['info_content'] }}</p>
                </aside>
            </div>
        @endif
    @endforeach
</div>
