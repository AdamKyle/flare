@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="{{!is_null($raid) ? 'Edit: ' . nl2br($raid->name) : 'Create New Race'}}"
            buttons="true"
            backUrl="{{!is_null($raid) ? route('raids.raid', ['raid' => $raid->id]) : route('admin.raids.list')}}"
        >
            <x-core.form-wizard.container action="{{route('admin.raids.store')}}" modelId="{{!is_null($raid) ? $raid->id : 0}}" lastTab="tab-style-2-5">
                <x-core.form-wizard.tabs>
                    <x-core.form-wizard.tab target="tab-style-2-1" primaryTitle="Basic Info" secondaryTitle="Basic information about the race." isActive="true"/>
                </x-core.form-wizard.tabs>
                <x-core.form-wizard.contents>
                    <x-core.form-wizard.content target="tab-style-2-1" isOpen="true">
                        <h3 class="mb-3">Basic Info</h3>
                        <x-core.forms.input :model="$raid" label="Name:" modelKey="name" name="name" />
                        <x-core.forms.text-area :model="$raid" label="Story:" modelKey="story" name="story" />
                        <x-core.forms.collection-select :model="$raid" label="Raid Boss:" modelKey="raid_boss_id" name="raid_boss_id" value="id" key="name" :options="$raidBosses" />
                        <x-core.forms.collection-select :model="$raid" label="Raid Boss Location:" modelKey="raid_boss_location_id" name="raid_boss_location_id" value="id" key="name" :options="$locations" />
                        <x-core.forms.collection-select-no-model label="Raid Monsters"
                                                                 name="raid_monster_ids[]"
                                                                 key="name"
                                                                 value="id"
                                                                 :options="$monsters"
                                                                 :relationIds="is_null($raid) ? [] : $raid->raid_monster_ids"
                        />
                        <x-core.forms.collection-select-no-model label="Raid Corrupted Locations (Optional)"
                                                                 name="corrupted_location_ids[]"
                                                                 key="name"
                                                                 value="id"
                                                                 :options="$locations"
                                                                 :relationIds="is_null($raid) ? [] : $raid->corrupted_location_ids"
                        />
                    </x-core.form-wizard.content>
                </x-core.form-wizard.contents>
            </x-core.form-wizard.container>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
