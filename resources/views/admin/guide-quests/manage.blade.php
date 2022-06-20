@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="{{!is_null($guideQuest) ? 'Edit: ' . nl2br($guideQuest->name) : 'Create Guide Quest'}}"
            buttons="true"
            backUrl="{{!is_null($guideQuest) ? route('admin.guide-quests.show', ['guideQuest' => $guideQuest->id]) : route('admin.guide-quests')}}"
        >
            <x-core.form-wizard.container action="{{route('admin.guide-quests.store')}}" modelId="{{!is_null($guideQuest) ? $guideQuest->id : 0}}" lastTab="tab-style-2-5">
                <x-core.form-wizard.tabs>
                    <x-core.form-wizard.tab target="tab-style-2-1" primaryTitle="Basic Info" secondaryTitle="Basic quest info." isActive="true"/>
                    <x-core.form-wizard.tab target="tab-style-2-2" primaryTitle="Requirements" secondaryTitle="Requirments" />
                </x-core.form-wizard.tabs>

                <x-core.form-wizard.contents>
                    <x-core.form-wizard.content target="tab-style-2-1" isOpen="true">
                        <h3 class="mb-3">Basic Info</h3>
                        <x-core.forms.input :model="$guideQuest" label="Name:" modelKey="name" name="name" />
                        <x-core.forms.input :model="$guideQuest" label="Reward Level:" modelKey="reward_level" name="reward_level" />
                        <x-core.forms.quill-editor type="normal" :model="$guideQuest" label="Guide Text:" modelKey="intro_text" name="intro_text" quillId="intro-text"/>
                        <x-core.forms.quill-editor type="html" :model="$guideQuest" label="Instructions:" modelKey="instructions" name="instructions" quillId="quest-instructions"/>
                    </x-core.form-wizard.content>

                    <x-core.form-wizard.content target="tab-style-2-2">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <h3 class="mb-3">Required levels for completion</h3>
                                <x-core.forms.input :model="$guideQuest" label="Required (Player) Level:" modelKey="required_level" name="required_level" />
                                <x-core.forms.key-value-select :model="$guideQuest" label="Required Skill:" modelKey="required_skill" name="required_skill" :options="$gameSkills"/>
                                <x-core.forms.input :model="$guideQuest" label="Required (Skill) Level:" modelKey="required_skill_level" name="required_skill_level" />
                                <x-core.forms.key-value-select :model="$guideQuest" label="Required Faction:" modelKey="required_faction_id" name="required_faction_id" :options="$gameMaps"/>
                                <x-core.forms.input :model="$guideQuest" label="Required (Faction) Level:" modelKey="required_faction_level" name="required_faction_level" />
                            </div>
                            <div class='border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                            <div>
                                <h3 class="mb-3">Other Requirements</h3>
                                <x-core.forms.key-value-select :model="$guideQuest" label="Required Plane Access:" modelKey="required_game_map_id" name="required_game_map_id" :options="$gameMaps"/>
                                <x-core.forms.key-value-select :model="$guideQuest" label="Required Quest (Complete):" modelKey="required_quest_id" name="required_quest_id" :options="$quests"/>
                                <x-core.forms.key-value-select :model="$guideQuest" label="Required Quest Item:" modelKey="required_quest_item_id" name="required_quest_item_id" :options="$questItems"/>
                            </div>
                        </div>

                        <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>

                        <h3 class="mb-3">Currency Rewards</h3>
                        <x-core.forms.input :model="$guideQuest" label="Gold Dust Reward:" modelKey="gold_dust_reward" name="gold_dust_reward" />
                        <x-core.forms.input :model="$guideQuest" label="Shards Reward:" modelKey="shards_reward" name="shards_reward" />

                    </x-core.form-wizard.content>
                </x-core.form-wizard.contents>
            </x-core.form-wizard.container>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
