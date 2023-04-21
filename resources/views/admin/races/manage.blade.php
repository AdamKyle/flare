@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="{{!is_null($race) ? 'Edit: ' . nl2br($race->name) : 'Create New Race'}}"
            buttons="true"
            backUrl="{{!is_null($race) ? route('races.race', ['race' => $race->id]) : route('races.list')}}"
        >
            <x-core.form-wizard.container action="{{route('races.store')}}" modelId="{{!is_null($race) ? $race->id : 0}}" lastTab="tab-style-2-5">
                <x-core.form-wizard.tabs>
                    <x-core.form-wizard.tab target="tab-style-2-1" primaryTitle="Basic Info" secondaryTitle="Basic information about the race." isActive="true"/>
                    <x-core.form-wizard.tab target="tab-style-2-2" primaryTitle="Skill Info" secondaryTitle="Basic information about the races effects on skills."/>
                </x-core.form-wizard.tabs>
                <x-core.form-wizard.contents>
                    <x-core.form-wizard.content target="tab-style-2-1" isOpen="true">
                        <h3 class="mb-3">Basic Info</h3>
                        <x-core.forms.input :model="$race" label="Name:" modelKey="name" name="name" />
                        <x-core.forms.input :model="$race" label="Strength Modifier:" modelKey="str_mod" name="str_mod" />
                        <x-core.forms.input :model="$race" label="Dexterity Modifier:" modelKey="dex_mod" name="dex_mod" />
                        <x-core.forms.input :model="$race" label="Intelligence Modifier:" modelKey="int_mod" name="int_mod" />
                        <x-core.forms.input :model="$race" label="Agility Modifier:" modelKey="agi_mod" name="agi_mod" />
                        <x-core.forms.input :model="$race" label="Charisma Modifier:" modelKey="chr_mod" name="chr_mod" />
                        <x-core.forms.input :model="$race" label="Durability Modifier:" modelKey="dur_mod" name="dur_mod" />
                        <x-core.forms.input :model="$race" label="Focus Modifier:" modelKey="focus_mod" name="focus_mod" />
                        <x-core.forms.input :model="$race" label="Defence Modifier:" modelKey="defense_mod" name="defense_mod" />
                    </x-core.form-wizard.content>

                    <x-core.form-wizard.content target="tab-style-2-2">
                        <h3 class="mb-3">Basic Skill Info</h3>
                        <x-core.forms.input :model="$race" label="Accuracy Modifier:" modelKey="accuracy_mod" name="accuracy_mod" />
                        <x-core.forms.input :model="$race" label="Dodge Modifier:" modelKey="dodge_mod" name="dodge_mod" />
                        <x-core.forms.input :model="$race" label="Looting Modifier:" modelKey="looting_mod" name="looting_mod" />
                    </x-core.form-wizard.content>
                </x-core.form-wizard.contents>
            </x-core.form-wizard.container>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
