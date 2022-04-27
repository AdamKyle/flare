@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="{{!is_null($itemAffix) ? 'Edit: ' . nl2br($itemAffix->name) : 'Create New Affix'}}"
            buttons="true"
            backUrl="{{!is_null($itemAffix) ? route('affixes.affix', ['affix' => $itemAffix->id]) : route('affixes.list')}}"
        >
            <x-core.form-wizard.container action="{{route('affixes.store')}}" modelId="{{!is_null($itemAffix) ? $itemAffix->id : 0}}" lastTab="tab-style-2-5">
                <x-core.form-wizard.tabs>
                    <x-core.form-wizard.tab target="tab-style-2-1" primaryTitle="Basic Info" secondaryTitle="Basic information about the affix." isActive="true"/>
                    <x-core.form-wizard.tab target="tab-style-2-2" primaryTitle="Stats & Modifiers" secondaryTitle="Stats and modifiers."/>
                    <x-core.form-wizard.tab target="tab-style-2-3" primaryTitle="Skill Effects" secondaryTitle="Skill Details."/>
                    <x-core.form-wizard.tab target="tab-style-2-4" primaryTitle="Effects" secondaryTitle="Other effects."/>
                    <x-core.form-wizard.tab target="tab-style-2-5" primaryTitle="Crafting" secondaryTitle="Crafting Details."/>
                </x-core.form-wizard.tabs>

                <x-core.form-wizard.contents>
                    <x-core.form-wizard.content target="tab-style-2-1" isOpen="true">
                        <h3 class="mb-3">Basic Item Info</h3>
                        <x-core.forms.input :model="$itemAffix" label="Name:" modelKey="name" name="name" />
                        <x-core.forms.select :model="$itemAffix" label="Type:" modelKey="type" name="type" :options="$types" />
                        <x-core.forms.text-area :model="$itemAffix" label="Description:" modelKey="description" name="description" />
                        <x-core.forms.check-box :model="$itemAffix" label="Can Affix Drop?" modelKey="can_drop" name="can_drop" />
                    </x-core.form-wizard.content>
                    <x-core.form-wizard.content target="tab-style-2-2">
                        <div class="grid md:grid-cols-2 gap-2">
                            <div>
                                <h3 class="mb-3">Stat Info</h3>
                                <x-core.forms.input :model="$itemAffix" label="Str Modifier (%):" modelKey="str_mod" name="str_mod" />
                                <x-core.forms.input :model="$itemAffix" label="Dex Modifier (%):" modelKey="dex_mod" name="dex_mod" />
                                <x-core.forms.input :model="$itemAffix" label="Dur Modifier (%):" modelKey="dur_mod" name="dur_mod" />
                                <x-core.forms.input :model="$itemAffix" label="Agi Modifier (%):" modelKey="agi_mod" name="agi_mod" />
                                <x-core.forms.input :model="$itemAffix" label="Int Modifier (%):" modelKey="int_mod" name="int_mod" />
                                <x-core.forms.input :model="$itemAffix" label="Chr Modifier (%):" modelKey="chr_mod" name="chr_mod" />
                                <x-core.forms.input :model="$itemAffix" label="Focus Modifier (%):" modelKey="focus_mod" name="focus_mod" />
                            </div>
                            <div class='border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                            <div>
                                <h3 class="mb-3">Attack/Def/Healing Modifiers</h3>
                                <x-core.forms.input :model="$itemAffix" label="Base Attack:" modelKey="base_damage_mod" name="base_damage_mod" />
                                <x-core.forms.input :model="$itemAffix" label="Base AC:" modelKey="base_ac_mod" name="base_ac_mod" />
                                <x-core.forms.input :model="$itemAffix" label="Base healing:" modelKey="base_healing_mod" name="base_healing_mod" />

                                <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                                <h3 class="mb-3">Class Bonus</h3>
                                <x-core.forms.input :model="$itemAffix" label="Class Bonus:" modelKey="class_bonus" name="base_heaclass_bonusling_mod" />
                            </div>
                        </div>
                    </x-core.form-wizard.content>
                    <x-core.form-wizard.content target="tab-style-2-3">
                        <div class="grid md:grid-cols-2 gap-2">
                            <div>
                                <h3 class="mb-3">Basic Info</h3>
                                <x-core.forms.select :model="$itemAffix" label="Affects Skill:" modelKey="skill_name" name="skill_name" :options="$skills" />
                                <x-core.forms.input :model="$itemAffix" label="Skill Training Bonus (%):" modelKey="skill_training_bonus" name="skill_training_bonus" />
                                <x-core.forms.input :model="$itemAffix" label="Skill Bonus (%):" modelKey="skill_bonus" name="skill_bonus" />
                            </div>
                            <div class='border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                            <div>
                                <h3 class="mb-3">Modifiers</h3>
                                <x-core.forms.input :model="$itemAffix" label="Skill Attack Modifier (%):" modelKey="base_damage_mod_bonus" name="base_damage_mod_bonus" />
                                <x-core.forms.input :model="$itemAffix" label="Skill AC Modifier (%):" modelKey="base_ac_mod_bonus" name="base_ac_mod_bonus" />
                                <x-core.forms.input :model="$itemAffix" label="Skill Healing Modifier(%):" modelKey="base_healing_mod_bonus" name="base_healing_mod_bonus" />
                                <x-core.forms.input :model="$itemAffix" label="Fight Timeout Modifier (%):" modelKey="fight_time_out_mod_bonus" name="fight_time_out_mod_bonus" />
                                <x-core.forms.input :model="$itemAffix" label="Mover Timeout Modifier (%):" modelKey="move_time_out_mod_bonus" name="move_time_out_mod_bonus" />
                            </div>
                        </div>
                    </x-core.form-wizard.content>
                    <x-core.form-wizard.content target="tab-style-2-4">
                        <div class="grid md:grid-cols-2 gap-2">
                            <div>
                                <h3 class="mb-3">Enemy Stat Reduction</h3>
                                <div class="mt-4 mb-4">
                                    <x-core.alerts.info-alert title="Attn!">
                                        The logic states that prefixes can reduce all stats and do not stack, while suffixes can reduce individual stats and do stack.
                                    </x-core.alerts.info-alert>
                                </div>
                                <x-core.forms.check-box :model="$itemAffix" label="Can Reduce Enemy Stats?" modelKey="reduces_enemy_stats" name="reduces_enemy_stats" />
                                <x-core.forms.input :model="$itemAffix" label="Str Reduction (%):" modelKey="str_reduction" name="str_reduction" />
                                <x-core.forms.input :model="$itemAffix" label="Dex Reduction (%):" modelKey="dex_reduction" name="dex_reduction" />
                                <x-core.forms.input :model="$itemAffix" label="Dur Reduction (%):" modelKey="dur_reduction" name="dur_reduction" />
                                <x-core.forms.input :model="$itemAffix" label="Agi Reduction (%):" modelKey="agi_reduction" name="agi_reduction" />
                                <x-core.forms.input :model="$itemAffix" label="Int Reduction (%):" modelKey="int_reduction" name="int_reduction" />
                                <x-core.forms.input :model="$itemAffix" label="Chr Reduction (%):" modelKey="chr_reduction" name="chr_reduction" />
                                <x-core.forms.input :model="$itemAffix" label="Focus Reduction (%):" modelKey="focus_reduction" name="focus_reduction" />
                            </div>
                            <div class='border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                            <div>
                                <h3 class="mb-3">Other Effects</h3>
                                <x-core.forms.input :model="$itemAffix" label="Enemy Resistance Reduction (%):" modelKey="resistance_reduction" name="resistance_reduction" />
                                <x-core.forms.input :model="$itemAffix" label="Enemy Skill Reduction (%):" modelKey="skill_reduction" name="skill_reduction" />
                                <x-core.forms.input :model="$itemAffix" label="Steal Life (%):" modelKey="steal_life_amount" name="steal_life_amount" />
                                <x-core.forms.input :model="$itemAffix" label="Entrance Chance (%):" modelKey="entranced_chance" name="entranced_chance" />

                                <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                                <h3 class='mb-3'>Damage Info</h3>
                                <x-core.forms.input :model="$itemAffix" label="Damage:" modelKey="damage" name="damage" />
                                <x-core.forms.check-box :model="$itemAffix" label="Is Damage Irresistible?" modelKey="irresistible_damage" name="irresistible_damage" />
                                <x-core.forms.check-box :model="$itemAffix" label="Can Damage Stack?" modelKey="damage_can_stack" name="damage_can_stack" />
                            </div>
                        </div>
                    </x-core.form-wizard.content>
                    <x-core.form-wizard.content target="tab-style-2-5">
                        <h3 class="mb-4">Crafting Details</h3>
                        <x-core.forms.input :model="$itemAffix" label="Cost:" modelKey="cost" name="cost" />
                        <x-core.forms.input :model="$itemAffix" label="Skill Level Required:" modelKey="skill_level_required" name="skill_level_required" />
                        <x-core.forms.input :model="$itemAffix" label="Skill Level Trivial:" modelKey="skill_level_trivial" name="skill_level_trivial" />
                        <x-core.forms.input :model="$itemAffix" label="Intelligence Required:" modelKey="int_required" name="int_required" />
                    </x-core.form-wizard.content>
                </x-core.form-wizard.contents>
            </x-core.form-wizard.container>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
