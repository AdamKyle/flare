@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="{{!is_null($quest) ? 'Edit: ' . $quest->name : 'Create Quest'}}"
            buttons="true"
            backUrl="{{!is_null($quest) ? route('quests.index') : route('quests.index')}}"
        >
            <x-core.form-wizard.container action="{{route('quest.store')}}" modelId="{{!is_null($quest) ? $quest->id : 0}}" lastTab="tab-style-2-5">
                <x-core.form-wizard.tabs>
                    <x-core.form-wizard.tab target="tab-style-2-1" primaryTitle="Basic Info" secondaryTitle="Basic information about the quest." isActive="true"/>
                    <x-core.form-wizard.tab target="tab-style-2-2" primaryTitle="Requirements" secondaryTitle="Quest requirements."/>
                    <x-core.form-wizard.tab target="tab-style-2-3" primaryTitle="Rewards" secondaryTitle="Quest rewards."/>
                </x-core.form-wizard.tabs>

                <x-core.form-wizard.contents>
                    <x-core.form-wizard.content target="tab-style-2-1" isOpen="true">
                        <h3 class="mb-3">Basic Quest Info</h3>
                        <x-core.forms.input :model="$quest" label="Name:" modelKey="name" name="name" />
                        <x-core.forms.key-value-select :model="$quest" label="Belongs To NPC:" modelKey="npc_id" name="npc_id" :options="$npcs" />
                        <x-core.forms.quill-editor type="normal" :model="$quest" label="Before Completion text:" modelKey="before_completion_description" name="before_completion_description" quillId="before-completion-text"/>
                        <x-core.forms.quill-editor type="normal" :model="$quest" label="After Completion text:" modelKey="after_completion_description" name="after_completion_description" quillId="after-completion-text"/>

                    </x-core.form-wizard.content>

                    <x-core.form-wizard.content target="tab-style-2-2">
                        <h3 class="mb-3">Required Items</h3>
                        <x-core.forms.key-value-select :model="$quest" label="Required Item:" modelKey="item_id" name="item_id" :options="$questItems" />
                        <x-core.forms.key-value-select :model="$quest" label="Secondary Required Item:" modelKey="secondary_required_item" name="secondary_required_item" :options="$questItems" />

                        <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                        <h3 class="mb-3">Quest RelationShip</h3>
                        <x-core.forms.key-value-select :model="$quest" label="Parent Quest:" modelKey="parent_quest_id" name="parent_quest_id" :options="$quests" />
                        <x-core.forms.check-box :model="$quest" label="Is Parent?" modelKey="is_parent" name="is_parent" />

                        <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                        <h3 class="mb-3">Faction Requirements</h3>
                        <x-core.forms.key-value-select :model="$quest" label="Faction Map:" modelKey="faction_game_map_id" name="faction_game_map_id" :options="$gameMaps" />
                        <x-core.forms.input :model="$quest" label="Faction Level:" modelKey="required_faction_level" name="required_faction_level" />

                        <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                        <h3 class="mb-3">Cost Requirements</h3>
                        <x-core.forms.input :model="$quest" label="Gold Cost:" modelKey="gold_cost" name="gold_cost" />
                        <x-core.forms.input :model="$quest" label="Gold Dust Cost:" modelKey="gold_dust_cost" name="gold_dust_cost" />
                        <x-core.forms.input :model="$quest" label="Shards Cost:" modelKey="shard_cost" name="shard_cost" />
                        <x-core.forms.input :model="$quest" label="Copper Coins Cost:" modelKey="copper_coin_cost" name="copper_coin_cost" />
                    </x-core.form-wizard.content>

                    <x-core.form-wizard.content target="tab-style-2-3">
                        <h3 class="mb-3">Currencies Reward</h3>
                        <x-core.forms.input :model="$quest" label="Gold Reward:" modelKey="reward_gold" name="reward_gold" />
                        <x-core.forms.input :model="$quest" label="Gold Dust Reward:" modelKey="reward_gold_dust" name="reward_gold_dust" />
                        <x-core.forms.input :model="$quest" label="Shards Reward:" modelKey="reward_shards" name="reward_shards" />

                        <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                        <h3 class="mb-3">XP Reward</h3>
                        <x-core.forms.input :model="$quest" label="XP Reward:" modelKey="reward_xp" name="reward_xp" />

                        <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                        <h3 class='mb-3'>Unlocks Skill</h3>
                        <x-core.forms.check-box :model="$quest" label="Unlocks skill?" modelKey="unlocks_skill" name="unlocks_skill" />
                        <x-core.forms.key-value-select :model="$quest" label="Unlocks Skill Type: " modelKey="unlocks_skill_type" name="unlocks_skill_type" :options="$skillTypes" />

                        <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                        <h3 class='mb-3'>Unlocks Feature</h3>
                        <x-core.forms.key-value-select :model="$quest" label="Unlocks Feature Type: " modelKey="unlocks_feature" name="unlocks_feature" :options="$unlocksFeatures" />

                        <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                        <h3 class='mb-3'>Quest Item Reward</h3>
                        <x-core.forms.key-value-select :model="$quest" label="Quest item Reward:" modelKey="reward_item" name="reward_item" :options="$questItems" />
                    </x-core.form-wizard.content>
                </x-core.form-wizard.contents>
            </x-core.form-wizard.container>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
