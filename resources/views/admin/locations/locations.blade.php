@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <x-core.page-title
        title="Locations"
        route="{{route('home')}}"
        color="success" link="Home"
    >
        <x-core.buttons.link-buttons.primary-button
            href="{{route('locations.create')}}"
            css="tw-ml-2"
        >
            Create Location
        </x-core.buttons.link-buttons.primary-button>
{{--        <x-core.buttons.link-buttons.primary-button--}}
{{--            href="{{route('items.export')}}"--}}
{{--            css="tw-ml-2"--}}
{{--        >--}}
{{--            <i class="fas fa-file-export"></i> Export--}}
{{--        </x-core.buttons.link-buttons.primary-button>--}}
{{--        <x-core.buttons.link-buttons.primary-button--}}
{{--            href="{{route('items.import')}}"--}}
{{--            css="tw-ml-2"--}}
{{--        >--}}
{{--            <i class="fas fa-file-upload"></i> Import--}}
{{--        </x-core.buttons.link-buttons.primary-button>--}}
    </x-core.page-title>
    @livewire('admin.locations.locations-table')
</div>
@endsection
