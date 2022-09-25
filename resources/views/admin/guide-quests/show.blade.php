@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.page-title
            title="{{$guideQuest->name}}"
            route="{{auth()->user()->hasRole('Admin') ? route('admin.guide-quests') : route('completed.guide-quests', ['user' => auth()->user()->id])}}"
            color="success" link="{{auth()->user()->hasRole('Admin') ? 'Guide Quests' : 'Back'}}"
        >
            @if (auth()->user()->hasRole('Admin'))
                <x-core.buttons.link-buttons.primary-button
                    href="{{route('admin.guide-quests.edit', ['guideQuest' => $guideQuest->id])}}"
                >
                    Edit Quest
                </x-core.buttons.link-buttons.primary-button>
            @endif
        </x-core.page-title>

        <x-core.cards.card>
            <div class='grid md:grid-cols-2 gap-4'>
                <div>
                    <h3 class="text-sky-600 dark:text-sky-500">Reward Details</h3>
                    <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                    <dl class="mb-4">
                        <dt>Reward Level</dt>
                        <dd>{{$guideQuest->reward_level}}</dd>
                    </dl>
                    <p class="mb-5">
                        Refers to the level of item between 1 and X that will be generated with random affixes.
                    </p>
                    @if (!is_null($guideQuest->faction_points_per_kill))
                        <h3 class="text-sky-600 dark:text-sky-500">Extra Bonuses</h3>
                        <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                        <dl class='mb-5'>
                            <dt>Faction Points per Kill</dt>
                            <dd>{{$guideQuest->faction_points_per_kill}}</dd>
                        </dl>
                    @endif
                    <h3 class="text-sky-600 dark:text-sky-500">Requirements</h3>
                    <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                    <dl class='mb-5'>
                        @if (!is_null($guideQuest->required_level))
                            <dt>Required Player Level</dt>
                            <dd>{{$guideQuest->required_level}}</dd>
                        @endif
                        @if (!is_null($guideQuest->skill_name))
                            <dt>Required Skill</dt>
                            <dd>{{$guideQuest->skill_name}}</dd>
                            <dt>Required Skill Level</dt>
                            <dd>{{$guideQuest->required_skill_level}}</dd>
                        @endif
                        @if (!is_null($guideQuest->faction_name))
                            <dt>Required Faction</dt>
                            <dd>{{$guideQuest->faction_name}}</dd>
                            <dt>Required Faction Level</dt>
                            <dd>{{$guideQuest->required_faction_level}}</dd>
                        @endif
                        @if (!is_null($guideQuest->quest_name))
                            <dt>Required Quest</dt>
                            <dd>{{$guideQuest->quest_name}}</dd>
                        @endif
                        @if (!is_null($guideQuest->required_quest_item_id))
                            <dt>Required Quest Item</dt>
                            <dd>{{$guideQuest->quest_item_name}}</dd>
                        @endif
                        @if (!is_null($guideQuest->required_kingdoms))
                            <dt>Required Kingdom Amount</dt>
                            <dd>{{$guideQuest->required_kingdoms}}</dd>
                        @endif
                        @if (!is_null($guideQuest->required_kingdom_level))
                            <dt>Required Kingdom Building Level (combined)</dt>
                            <dd>{{$guideQuest->required_kingdom_level}}</dd>
                        @endif
                        @if (!is_null($guideQuest->required_kingdom_units))
                            <dt>Required Kingdom Units (combined)</dt>
                            <dd>{{$guideQuest->required_kingdom_units}}</dd>
                        @endif
                        @if (!is_null($guideQuest->required_passive_skill))
                            <dt>Required Passive Name</dt>
                            <dd>{{$guideQuest->passive_name}}</dd>
                            <dt>Required Passive Level</dt>
                            <dd>{{$guideQuest->required_passive_level}}</dd>
                        @endif
                    </dl>
                    <h3 class="text-sky-600 dark:text-sky-500">Currency Rewards</h3>
                    <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                    <dl>
                        <dt>Gold Dust Reward</dt>
                        <dd>{{is_null($guideQuest->gold_dust_reward) ? 0 : number_format($guideQuest->gold_dust_reward)}}</dd>
                        <dt>Shards Reward</dt>
                        <dd>{{is_null($guideQuest->shards_reward) ? 0 : number_format($guideQuest->shards_reward)}}</dd>
                    </dl>
                </div>
                <div class='block md:hidden border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                <div>
                    <div class="border-1 rounded-sm p-2 bg-slate-300 dark:bg-slate-700 max-h-[250px] overflow-x-auto mb-4">
                        <h3 class="mb-4">Intro Text</h3>
                        <div>
                            {!! nl2br($guideQuest->intro_text) !!}
                        </div>
                    </div>

                    <div class="border-1 rounded-sm p-2 bg-slate-300 dark:bg-slate-700 max-h-[250px] overflow-x-auto">
                        <h3 class="mb-4">Instructions</h3>
                        <div class="guide-quest-instructions">
                            {!! $guideQuest->instructions !!}
                        </div>
                    </div>
                </div>
            </div>
        </x-core.cards.card>
    </x-core.layout.info-container>
@endsection
