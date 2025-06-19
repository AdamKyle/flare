@extends('layouts.app')

@section('content')
    <div class="w-full md:w-3/4 mx-auto my-4 px-4">
        <x-core.page.title
            title="Locations"
            route="{{route('home')}}"
            color="success"
            link="Home"
        >
            <x-core.buttons.link-buttons.primary-button
                href="{{route('locations.create')}}"
                css="tw-ml-2"
            >
                Create Location
            </x-core.buttons.link-buttons.primary-button>
            <x-core.buttons.link-buttons.primary-button
                href="{{route('locations.export')}}"
                css="tw-ml-2"
            >
                <i class="fas fa-file-export"></i>
                Export
            </x-core.buttons.link-buttons.primary-button>
            <x-core.buttons.link-buttons.primary-button
                href="{{route('locations.import')}}"
                css="tw-ml-2"
            >
                <i class="fas fa-file-upload"></i>
                Import
            </x-core.buttons.link-buttons.primary-button>
        </x-core.page.title>
        @livewire('admin.locations.locations-table')
    </div>
@endsection
