@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="{{!is_null($classSpecial) ? 'Edit: ' . nl2br($classSpecial->name) : 'Create New Race'}}"
            buttons="true"
            backUrl="{{!is_null($classSpecial) ? route('class-specials.show', ['gameClassSpecial' => $classSpecial->id]) : route('class-specials.list')}}"
        >
            <x-core.form-wizard.container action="{{route('class-specials.store')}}" modelId="{{!is_null($classSpecial) ? $classSpecial->id : 0}}" lastTab="tab-style-2-5">
                <x-core.form-wizard.tabs>
                    <x-core.form-wizard.tab target="tab-style-2-1" primaryTitle="Basic Info" secondaryTitle="Basic details about the class special" isActive="true"/>
                    <x-core.form-wizard.tab target="tab-style-2-2" primaryTitle="Modifiers" secondaryTitle="The modifiers this specialty effects"/>
                </x-core.form-wizard.tabs>
                <x-core.form-wizard.contents>
                    <x-core.form-wizard.content target="tab-style-2-1" isOpen="true">
                        <h3 class="mb-3">Basic Info</h3>
                        <x-core.forms.input :model="$classSpecial" label="Name:" modelKey="name" name="name" />
                        <x-core.forms.key-value-select :model="$classSpecial" label="Belongs to class:" modelKey="game_class_id" name="game_class_id" :options="$gameClasses"/>
                        <x-core.forms.input :model="$classSpecial" label="Class rank level required:" modelKey="requires_class_rank_level" name="requires_class_rank_level" />
                        <x-core.forms.text-area :model="$classSpecial" label="Description:" modelKey="description" name="description" />
                    </x-core.form-wizard.content>

                    <x-core.form-wizard.content target="tab-style-2-2">
                        <h3 class="mb-3">Damage Info</h3>
                        <x-core.forms.input :model="$classSpecial" label="Damage (optional):" modelKey="specialty_damage" name="specialty_damage" />
                        <x-core.forms.input :model="$classSpecial" label="Damage Increase per Level (optional):" modelKey="increase_specialty_damage_per_level" name="increase_specialty_damage_per_level" />
                        <x-core.forms.input :model="$classSpecial" label="Damage Stat % towards Damage (optional):" modelKey="specialty_damage_uses_damage_stat_amount" name="specialty_damage_uses_damage_stat_amount" />
                        <x-core.forms.key-value-select :model="$classSpecial" label="Attack Type Required:" modelKey="attack_type_required" name="attack_type_required" :options="$forAttackType"/>
                        <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                        <h3 class="mb-3">Modifiers</h3>
                        <x-core.forms.input :model="$classSpecial" label="Base Damage Mod % (optional):" modelKey="base_damage_mod" name="base_damage_mod" />
                        <x-core.forms.input :model="$classSpecial" label="Base AC Mod % (optional):" modelKey="base_ac_mod" name="base_ac_mod" />
                        <x-core.forms.input :model="$classSpecial" label="Base Healing Mod % (optional):" modelKey="base_healing_mod" name="base_healing_mod" />
                        <x-core.forms.input :model="$classSpecial" label="Base Spell Damage Mod % (optional):" modelKey="base_spell_damage_mod" name="base_spell_damage_mod" />
                        <x-core.forms.input :model="$classSpecial" label="Base Health Mod % (optional):" modelKey="health_mod" name="health_mod" />
                        <x-core.forms.input :model="$classSpecial" label="Base Damage Stat % (optional):" modelKey="base_damage_stat_increase" name="base_damage_stat_increase" />
                    </x-core.form-wizard.content>
                </x-core.form-wizard.contents>
            </x-core.form-wizard.container>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
