@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="{{!is_null($skill) ? 'Edit: ' . nl2br($skill->name) : 'Create New Item'}}"
            buttons="true"
            backUrl="{{!is_null($skill) ? route('skills.skill', ['skill' => $skill->id]) : route('skills.list')}}"
        >
            <x-core.form-wizard.container action="{{route('skills.store')}}" modelId="{{!is_null($skill) ? $skill->id : 0}}" lastTab="tab-style-2-5">
                <x-core.form-wizard.tabs>
                    <x-core.form-wizard.tab target="tab-style-2-1" primaryTitle="Basic Info" secondaryTitle="Basic information about the skill." isActive="true"/>
                    <x-core.form-wizard.tab target="tab-style-2-2" primaryTitle="Modifiers" secondaryTitle="Setup modifiers for the skill." />
                </x-core.form-wizard.tabs>
                <x-core.form-wizard.contents>
                    <x-core.form-wizard.content target="tab-style-2-1" isOpen="true">
                        <x-core.forms.input :model="$skill" label="Name:" modelKey="name" name="name" />
                        <x-core.forms.text-area :model="$skill" label="Description:" modelKey="description" name="description" />
                        <x-core.forms.input :model="$skill" label="Max level:" modelKey="max_level" name="max_level" />
                        <x-core.forms.check-box :model="$skill" label="Can Skill Be Trained?" modelKey="can_train" name="can_train" />
                        <x-core.forms.check-box :model="$skill" label="Is Skill Locked?" modelKey="is_locked" name="is_locked" />
                        <x-core.forms.key-value-select :model="$skill" label="Type:" modelKey="type" name="type" :options="$skillTypes"/>
                    </x-core.form-wizard.content>
                    <x-core.form-wizard.content target="tab-style-2-2">
                        <div class="grid md:grid-cols-2 gap-2">
                            <div>
                                <h3 class="mb-3">Character Modifiers</h3>
                                <x-core.forms.input :model="$skill" label="Base Damage Modifier % (per level):" modelKey="base_damage_mod_bonus_per_level" name="base_damage_mod_bonus_per_level" />
                                <x-core.forms.input :model="$skill" label="Base Healing Modifier % (per level):" modelKey="base_healing_mod_per_level" name="base_healing_mod_per_level" />
                                <x-core.forms.input :model="$skill" label="Base AC Modifier % (per level):" modelKey="base_ac_mod_bonus_per_level" name="base_ac_mod_bonus_per_level" />

                                <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                                <h3 class="mb-3">Class Bonus (Optional)</h3>

                                <x-core.forms.input :model="$skill" label="Class Bonus (% Per Level):" modelKey="class_bonus" name="class_bonus" />

                                <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                                <h3 class="mb-3">Timer Modifiers</h3>

                                <x-core.forms.input :model="$skill" label="Fight Timeout Reduction % (per level):" modelKey="fight_time_out_mod_bonus_per_level" name="fight_time_out_mod_bonus_per_level" />
                                <x-core.forms.input :model="$skill" label="Move Timeout Reduction % (per level):" modelKey="move_time_out_mod_bonus_per_level" name="move_time_out_mod_bonus_per_level" />

                                <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                                <h3 class="mb-3">Misc</h3>
                                <x-core.forms.input :model="$skill" label="Skill Bonus Per Level (per level):" modelKey="skill_bonus_per_level" name="skill_bonus_per_level" />
                                <x-core.forms.key-value-select :model="$skill" label="Belongs To Class:" modelKey="game_class_id" name="game_class_id" :options="$gameClasses"/>
                            </div>
                            <div class='border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                            <div>
                                <h3 class="mb-3">Kingdom Modifiers</h3>
                                <x-core.forms.input :model="$skill" label="Unit Recruitment Time Reduction % (per level):" modelKey="unit_time_reduction" name="unit_time_reduction" />
                                <x-core.forms.input :model="$skill" label="Building Time Reduction % (per level):" modelKey="building_time_reduction" name="building_time_reduction" />
                                <x-core.forms.input :model="$skill" label="Unit Movement Time Reduction % (per level):" modelKey="unit_movement_time_reduction" name="unit_movement_time_reduction" />
                            </div>
                        </div>
                    </x-core.form-wizard.content>
                </x-core.form-wizard.contents>
            </x-core.form-wizard.container>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
