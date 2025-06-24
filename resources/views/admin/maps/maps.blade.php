@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <x-core.page.title
            title="Game Maps"
            route="{{route('home')}}"
            color="success"
            link="Home"
        >
            <x-core.buttons.link-buttons.primary-button
                href="{{route('maps.upload')}}"
                css="tw-ml-2"
            >
                Upload New Map
            </x-core.buttons.link-buttons.primary-button>
        </x-core.page.title>
        @livewire('admin.maps.maps-table')
    </div>
@endsection
