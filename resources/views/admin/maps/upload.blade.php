@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="{{ 'Upload New Map' }}"
            buttons="true"
            backUrl="{{ route('maps') }}"
        >
            <x-core.form-wizard.container
                action="{{ route('upload.map') }}"
                modelId="0"
                lastTab="tab-style-2-1"
                enctype="multipart/form-data"
            >
                <x-core.form-wizard.tabs>
                    <x-core.form-wizard.tab
                        target="tab-style-1-1"
                        primaryTitle="New Game Map"
                        secondaryTitle="Basic information about the map."
                        isActive="true"
                    />
                </x-core.form-wizard.tabs>
                <x-core.form-wizard.contents>
                    <x-core.form-wizard.content
                        target="tab-style-1-1"
                        isOpen="true"
                    >
                        <h3 class="mb-3">Map Details</h3>
                        <x-core.forms.input
                            :model="$mapDetails"
                            label="Name:"
                            modelKey="name"
                            name="name"
                        />
                        <x-core.forms.input
                            :model="$mapDetails"
                            label="Kingdom Color:"
                            modelKey="kingdom_color"
                            name="kingdom_color"
                        />
                        <x-core.forms.check-box
                            :model="$mapDetails"
                            label="Is Default"
                            modelKey="default"
                            name="default"
                        />
                        <x-core.forms.check-box
                            :model="$mapDetails"
                            label="Can Traverse"
                            modelKey="can_traverse"
                            name="can_traverse"
                        />
                        <x-core.forms.key-value-select
                            :model="$mapDetails"
                            label="Only For Event Type:"
                            modelKey="only_during_event_type"
                            name="only_during_event_type"
                            :options="$eventTypes"
                        />
                        <x-core.forms.file-upload
                            :model="$mapDetails"
                            label="Map:"
                            modelKey="map"
                            name="map"
                        />
                    </x-core.form-wizard.content>
                </x-core.form-wizard.contents>
            </x-core.form-wizard.container>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
