@php
    $atonementNames = \App\Game\Gems\Values\GemTypeValue::getNames();
    $craftingSkillNames = \App\Flare\Models\GameSkill::whereIn('id', $gameLocationGemParamters->crafting_skill_ids ?? [])
        ->orderBy('name')
        ->pluck('name')
        ->implode(', ');
    $locationRoute = auth()->check() && auth()->user()->hasRole('Admin')
        ? route('locations.location', ['location' => $gameLocationGemParamters->location])
        : route('info.page.location', ['location' => $gameLocationGemParamters->location]);
    $mapRoute = auth()->check() && auth()->user()->hasRole('Admin')
        ? route('map', ['gameMap' => $gameLocationGemParamters->location->map])
        : route('info.page.map', ['map' => $gameLocationGemParamters->location->map]);
    $sections = [
        'Player Rewards' => [
            'info_title' => 'Player reward modifiers',
            'info_content' => 'Stored percentage ranges that will later affect character progression, class progression, faction rewards, passive training, and crafting skill gains.',
            'fields' => [
                'character_xp_bonus_range' => ['Character XP Bonus Range', 'text-green-700 dark:text-green-400'],
                'character_class_rank_xp_bonus_range' => ['Character Class Rank XP Bonus Range', 'text-green-700 dark:text-green-400'],
                'character_class_specialty_xp_gain_range' => ['Character Class Specialty XP Gain Range', 'text-green-700 dark:text-green-400'],
                'faction_point_increase_range' => ['Faction Point Increase Range', 'text-green-700 dark:text-green-400'],
                'kingdom_passive_training_reduction_range' => ['Kingdom Passive Training Reduction Range', 'text-green-700 dark:text-green-400'],
                'crafting_skill_bonus_range' => ['Crafting Skill Bonus Range', 'text-green-700 dark:text-green-400'],
            ],
        ],
        'Currency and Drops' => [
            'info_title' => 'Currency and item rewards',
            'info_content' => 'Stored percentage ranges that will later increase currency gains and item drop chances, including Unique, Mythic, Cosmic, and Ascended item drop modifiers.',
            'fields' => [
                'gold_gain_range' => ['Gold Gain Range', 'text-green-700 dark:text-green-400'],
                'gold_dust_gain_range' => ['Gold Dust Gain Range', 'text-green-700 dark:text-green-400'],
                'shards_gain_range' => ['Shards Gain Range', 'text-green-700 dark:text-green-400'],
                'copper_coin_gain_range' => ['Copper Coin Gain Range', 'text-green-700 dark:text-green-400'],
                'item_drop_chance_increase_range' => ['Item Drop Chance Increase Range', 'text-green-700 dark:text-green-400'],
                'unique_item_drop_chance_increase_range' => ['Unique Item Drop Chance Increase Range', 'text-green-700 dark:text-green-400'],
                'mythic_item_drop_chance_increase_range' => ['Mythic Item Drop Chance Increase Range', 'text-green-700 dark:text-green-400'],
                'cosmic_item_drop_chance_increase_range' => ['Cosmic Item Drop Chance Increase Range', 'text-green-700 dark:text-green-400'],
                'ascended_item_drop_chance_increase_range' => ['Ascended Item Drop Chance Increase Range', 'text-green-700 dark:text-green-400'],
            ],
        ],
        'Enemy Combat' => [
            'info_title' => 'Enemy combat modifiers',
            'info_content' => 'Stored percentage ranges that will later increase enemy combat stats, healing, avoidance, resistances, and special combat chances.',
            'fields' => [
                'enemy_strength_increase_range' => ['Enemy Strength Increase Range', 'text-red-700 dark:text-red-400'],
                'enemy_healing_increase_range' => ['Enemy Healing Increase Range', 'text-red-700 dark:text-red-400'],
                'enemy_spell_evasion_range' => ['Enemy Spell Evasion Range', 'text-red-700 dark:text-red-400'],
                'enemy_affix_resistance_range' => ['Enemy Affix Resistance Range', 'text-red-700 dark:text-red-400'],
                'enemy_entrancing_chance_range' => ['Enemy Entrancing Chance Range', 'text-red-700 dark:text-red-400'],
                'enemy_devouring_light_chance_range' => ['Enemy Devouring Light Chance Range', 'text-red-700 dark:text-red-400'],
                'enemy_devouring_darkness_chance_range' => ['Enemy Devouring Darkness Chance Range', 'text-red-700 dark:text-red-400'],
                'enemy_ambush_chance_range' => ['Enemy Ambush Chance Range', 'text-red-700 dark:text-red-400'],
                'enemy_ambush_resistance_range' => ['Enemy Ambush Resistance Range', 'text-red-700 dark:text-red-400'],
                'enemy_counter_chance_range' => ['Enemy Counter Chance Range', 'text-red-700 dark:text-red-400'],
                'enemy_counter_resistance_range' => ['Enemy Counter Resistance Range', 'text-red-700 dark:text-red-400'],
            ],
        ],
        'Monster Rewards' => [
            'info_title' => 'Monster reward modifiers',
            'info_content' => 'Stored percentage ranges that will later increase rewards earned from monsters, including quest item chance, monster XP, and monster gold.',
            'fields' => [
                'enemy_quest_item_drop_chance_increase_range' => ['Enemy Quest Item Drop Chance Increase Range', 'text-green-700 dark:text-green-400'],
                'monster_xp_increase_range' => ['Monster XP Increase Range', 'text-green-700 dark:text-green-400'],
                'monster_gold_drop_increase_range' => ['Monster Gold Drop Increase Range', 'text-green-700 dark:text-green-400'],
            ],
        ],
    ];
@endphp

<div class="space-y-6 text-gray-700 dark:text-gray-300">
    <div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100">Description</h3>
        <p class="whitespace-pre-line text-sm leading-6 text-gray-700 dark:text-gray-300">{{ filled($gameLocationGemParamters->description) ? $gameLocationGemParamters->description : 'N/A' }}</p>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(18rem,24rem)] lg:items-start">
        <section class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">Overview</h3>

            <dl class="grid grid-cols-1 gap-x-8 gap-y-4 lg:grid-cols-[minmax(10rem,16rem)_minmax(0,1fr)_minmax(10rem,16rem)_minmax(0,1fr)]">
                <dt class="text-sm font-semibold leading-6 text-gray-900 dark:text-gray-100">Location</dt>
                <dd class="text-sm leading-6 text-gray-700 dark:text-gray-300">
                    <a href="{{ $locationRoute }}">{{ $gameLocationGemParamters->location->name }}</a>
                </dd>
                <dt class="text-sm font-semibold leading-6 text-gray-900 dark:text-gray-100">Game Map</dt>
                <dd class="text-sm leading-6 text-gray-700 dark:text-gray-300">
                    <a href="{{ $mapRoute }}">{{ $gameLocationGemParamters->location->map->name }}</a>
                </dd>
                @if(! is_null($gameLocationGemParamters->monster_atonement) && isset($atonementNames[$gameLocationGemParamters->monster_atonement]))
                    <dt class="text-sm font-semibold leading-6 text-gray-900 dark:text-gray-100">Monster Atonement</dt>
                    <dd class="text-sm leading-6 text-gray-700 dark:text-gray-300">{{ $atonementNames[$gameLocationGemParamters->monster_atonement] }}</dd>
                @endif
                @if(filled($gameLocationGemParamters->monster_atonement_range))
                    <dt class="text-sm font-semibold leading-6 text-gray-900 dark:text-gray-100">Monster Atonement Range</dt>
                    <dd class="text-sm leading-6 text-gray-700 dark:text-gray-300">+{{ $gameLocationGemParamters->monster_atonement_range }}%</dd>
                @endif
                @if($craftingSkillNames !== '')
                    <dt class="text-sm font-semibold leading-6 text-gray-900 dark:text-gray-100">Crafting Skills</dt>
                    <dd class="text-sm leading-6 text-gray-700 dark:text-gray-300">{{ $craftingSkillNames }}</dd>
                @endif
            </dl>
        </section>

        <aside class="self-start rounded-lg border border-blue-300 bg-blue-50 p-4 text-blue-900 dark:border-blue-700 dark:bg-blue-950 dark:text-blue-100">
            <h2 class="mb-2 text-lg font-semibold">What this setup controls</h2>
            <p class="text-sm leading-6">Shows the map or location this parameter setup belongs to, its monster atonement selection, and any crafting skills attached to it.</p>
        </aside>
    </div>

    @foreach($sections as $sectionTitle => $section)
        @php
            $visibleRows = collect($section['fields'])
                ->map(function (array $fieldConfig, string $field) use ($gameLocationGemParamters): ?array {
                    [$label, $colorClass] = $fieldConfig;
                    if (! filled($gameLocationGemParamters->{$field})) {
                        return null;
                    }
                    return ['label' => $label, 'value' => '+'.$gameLocationGemParamters->{$field}.'%', 'class' => $colorClass];
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
