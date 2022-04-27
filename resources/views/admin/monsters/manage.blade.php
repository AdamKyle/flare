@extends('layouts.app')

@section('content')

    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="{{!is_null($monster) ? 'Edit: ' . nl2br($monster->name) : 'Create New Monster'}}"
            buttons="true"
            backUrl="{{!is_null($monster) ? route('monsters.monster', ['monster' => $monster->id]) : route('monster.list')}}"
        >
            <x-core.form-wizard.container action="{{route('monster.store')}}" modelId="{{!is_null($monster) ? $monster->id : 0}}" lastTab="tab-style-2-5">
                <x-core.form-wizard.tabs>
                    <x-core.form-wizard.tab target="tab-style-2-1" primaryTitle="Basic Info" secondaryTitle="Basic monster info." isActive="true"/>
                    <x-core.form-wizard.tab target="tab-style-2-2" primaryTitle="Resistances" secondaryTitle="Resistances against player actions."/>
                    <x-core.form-wizard.tab target="tab-style-2-3" primaryTitle="Modifiers" secondaryTitle="Misc modifiers."/>
                    <x-core.form-wizard.tab target="tab-style-2-4" primaryTitle="Quest Item" secondaryTitle="Monsters quest item."/>
                </x-core.form-wizard.tabs>

                <x-core.form-wizard.contents>
                    <x-core.form-wizard.content target="tab-style-2-1" isOpen="true">
                        Content
                    </x-core.form-wizard.content>
                    <x-core.form-wizard.content target="tab-style-2-2">
                        Content
                    </x-core.form-wizard.content>
                    <x-core.form-wizard.content target="tab-style-2-3">
                        Content
                    </x-core.form-wizard.content>
                    <x-core.form-wizard.content target="tab-style-2-4">
                        Content
                    </x-core.form-wizard.content>
                </x-core.form-wizard.contents>
            </x-core.form-wizard.container>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
