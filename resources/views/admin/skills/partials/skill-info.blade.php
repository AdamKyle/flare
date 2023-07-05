
<x-core.layout.info-container>
    <x-core.page-title
        title="{{$skill->name}}"
        route="{{url()->previous()}}"
        color="success"
        link="Back"
    >
        @auth
            @if (auth()->user()->hasRole('Admin'))
                <x-core.buttons.link-buttons.primary-button
                    href="{{route('skill.edit', ['skill' => $skill])}}"
                    css="tw-ml-2"
                >
                    Edit Skill
                </x-core.buttons.link-buttons.primary-button>
            @endif
        @endauth
    </x-core.page-title>

    <x-core.cards.card>
        <p class="mb-4 mt-4">{!! nl2br(e($skill->description)) !!}</p>
        <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
        @if ($skill->skillType()->isCrafting())
            <p class="mb-4">
                This skill cannot be trained by fighting alone. Instead,
                by crafting weapons of this type you'll gain some xp towards its level.
                Certain quest items can help increase
                the amount of xp you get from training this skill.
            </p>

            <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
        @endif

        @if ($skill->skillType()->isEnchanting())
            <p class="mb-4">
                This skill requires you to enchant items. You can do this by clicking Craft/Enchant
                and selecting enchant. Specific quest items can help increase the amount of XP
                you get per successful attempt.
            </p>

            <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
        @endif

        @if ($skill->skillType()->isAlchemy())
            <p class="mb-4">
                This skill requires you to use Alchemy, which you can find under Craft/Enchant
                Assuming you have done the appropriate quest.
            </p>

            <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
        @endif


        <h3 class="text-sky-600 dark:text-sky-500">Core Stats</h3>
        <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
        <dl>
            <dt>Max Level:</dt>
            <dd>{{$skill->max_level}}</dd>
            <dt>Base Damage Mod At Max Level:</dt>
            <dd>{{($skill->base_damage_mod_bonus_per_level * $skill->max_level) * 100}}%</dd>
            <dt>Base Ac Mod At Max Level:</dt>
            <dd>{{($skill->base_ac_mod_bonus_per_level * $skill->max_level) * 100}}%</dd>
            <dt>Base Healing Mod At Max Level:</dt>
            <dd>{{($skill->base_healing_mod_bonus_per_level * $skill->max_level) * 100}}%</dd>
            <dt>Fight Timeout Mod At Max Level:</dt>
            <dd>{{($fightTimeOutMod = $skill->fight_time_out_mod_bonus_per_level * $skill->max_level) * 100}}%</dd>
            <dt>Move Timeout Mod At Max Level:</dt>
            <dd>{{($skill->move_time_out_mod_bonus_per_level * $skill->max_level) * 100}}%</dd>
            <dt>Class Bonus At Max Level:</dt>
            @if (!is_null($skill->class_bonus))
                <dd>{{($skill->class_bonus * $skill->max_level) * 100}}%</dd>
            @else
                <dd>0%</dd>
            @endif
            <dt>Skill Bonus At Max Level:</dt>
            @if ($skill->can_train)
                <dd>{{($skill->skill_bonus_per_level * $skill->max_level) * 100}}%</dd>
            @else
                <dd>{{($skill->skill_bonus_per_level * $skill->max_level) * 100}}%</dd>
            @endif
        </dl>

        @if ($skill->skillType()->effectsKingdom())
            <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
            <h3 class="text-sky-600 dark:text-sky-500">Effects Your Kingdoms</h3>
            <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
            <dl>
                <dt>Unit Recruitment Time Reduction:</dt>
                <dd>{{($skill->unit_time_reduction * $skill->max_level) * 100}}%</dd>
                <dt>Unit Movement Time Reduction:</dt>
                <dd>{{($skill->unit_movement_time_reduction * $skill->max_level) * 100}}%</dd>
                <dt>Building Upgrade/Repair Time Reduction:</dt>
                <dd>{{($skill->building_time_reduction * $skill->max_level) * 100}}%</dd>
            </dl>
        @endif
    </x-core.cards.card>
</x-core.layout.info-container>
