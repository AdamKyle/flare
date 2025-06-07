@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="{{!is_null($itemSkill) ? 'Edit: ' . nl2br($itemSkill->name) : 'Create New Item Skill'}}"
            buttons="true"
            backUrl="{{!is_null($itemSkill) ? route('admin.items-skills.show', ['itemSkill' => $itemSkill->id]) : route('admin.items-skills.list')}}"
        >
            <x-core.form-wizard.container
                action="{{route('admin.item-skills.store')}}"
                modelId="{{!is_null($itemSkill) ? $itemSkill->id : 0}}"
                lastTab="tab-style-2-5"
            >
                <x-core.form-wizard.tabs>
                    <x-core.form-wizard.tab
                        target="tab-style-2-1"
                        primaryTitle="Info"
                        secondaryTitle="Item Skill Details"
                        isActive="true"
                    />
                </x-core.form-wizard.tabs>
                <x-core.form-wizard.contents>
                    <x-core.form-wizard.content
                        target="tab-style-2-1"
                        isOpen="true"
                    >
                        <div class="mt-2">
                            <h3 class="mb-3">Basic Item Info</h3>
                            <x-core.forms.input
                                :model="$itemSkill"
                                label="Name:"
                                modelKey="name"
                                name="name"
                            />
                            <x-core.forms.text-area
                                :model="$itemSkill"
                                label="Description:"
                                modelKey="description"
                                name="description"
                            />
                        </div>
                        <div
                            class="border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-6"
                        ></div>
                        <div class="grid md:grid-cols-2 md:gap-3">
                            <div>
                                <h3 class="mb-3">Stat Info</h3>
                                <x-core.forms.input
                                    :model="$itemSkill"
                                    label="Str Modifier (%):"
                                    modelKey="str_mod"
                                    name="str_mod"
                                />
                                <x-core.forms.input
                                    :model="$itemSkill"
                                    label="Dex Modifier (%):"
                                    modelKey="dex_mod"
                                    name="dex_mod"
                                />
                                <x-core.forms.input
                                    :model="$itemSkill"
                                    label="Dur Modifier (%):"
                                    modelKey="dur_mod"
                                    name="dur_mod"
                                />
                                <x-core.forms.input
                                    :model="$itemSkill"
                                    label="Agi Modifier (%):"
                                    modelKey="agi_mod"
                                    name="agi_mod"
                                />
                                <x-core.forms.input
                                    :model="$itemSkill"
                                    label="Int Modifier (%):"
                                    modelKey="int_mod"
                                    name="int_mod"
                                />
                                <x-core.forms.input
                                    :model="$itemSkill"
                                    label="Chr Modifier (%):"
                                    modelKey="chr_mod"
                                    name="chr_mod"
                                />
                                <x-core.forms.input
                                    :model="$itemSkill"
                                    label="Focus Modifier (%):"
                                    modelKey="focus_mod"
                                    name="focus_mod"
                                />
                            </div>
                            <div
                                class="border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-6"
                            ></div>
                            <div>
                                <h3 class="mb-3">Attack/Def/Healing</h3>
                                <x-core.forms.input
                                    :model="$itemSkill"
                                    label="Base Attack:"
                                    modelKey="base_damage_mod"
                                    name="base_damage_mod"
                                />
                                <x-core.forms.input
                                    :model="$itemSkill"
                                    label="Base AC:"
                                    modelKey="base_ac_mod"
                                    name="base_ac_mod"
                                />
                                <x-core.forms.input
                                    :model="$itemSkill"
                                    label="Base healing:"
                                    modelKey="base_healing_mod"
                                    name="base_healing_mod"
                                />
                            </div>
                        </div>
                        <div
                            class="border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-6"
                        ></div>
                        <div class="grid md:grid-cols-2 md:gap-3">
                            <div>
                                <h3 class="mb-3">Level Info</h3>
                                <x-core.forms.input
                                    :model="$itemSkill"
                                    label="Max Level:"
                                    modelKey="max_level"
                                    name="max_level"
                                />
                                <x-core.forms.input
                                    :model="$itemSkill"
                                    label="Total Kills Per Level"
                                    modelKey="total_kills_needed"
                                    name="total_kills_needed"
                                />
                            </div>
                            <div
                                class="border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-6"
                            ></div>
                            <div>
                                <h3 class="mb-3">Parent Skill</h3>
                                <x-core.forms.key-value-select
                                    :model="$itemSkill"
                                    label="Parent Skill (Optional)"
                                    modelKey="parent_id"
                                    name="parent_id"
                                    :options="$parentSkills"
                                />
                                <x-core.forms.input
                                    :model="$itemSkill"
                                    label="Parent Skill Level Required (Optional):"
                                    modelKey="parent_level_needed"
                                    name="parent_level_needed"
                                />
                            </div>
                        </div>
                    </x-core.form-wizard.content>
                </x-core.form-wizard.contents>
            </x-core.form-wizard.container>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
