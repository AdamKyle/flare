@extends('layouts.app')

@section('content')
    <x-core.page-title
        title="Class Specials"
        route="{{route('home')}}"
        color="success" link="Home"
    >
        <x-core.buttons.link-buttons.primary-button
            href="{{route('class-specials.create')}}"
            css="tw-ml-2"
        >
            Create Class Special
        </x-core.buttons.link-buttons.primary-button>
        <x-core.buttons.link-buttons.primary-button
            href="{{route('class-specials.show-export')}}"
            css="tw-ml-2"
        >
            <i class="fas fa-file-export"></i> Export
        </x-core.buttons.link-buttons.primary-button>
        <x-core.buttons.link-buttons.primary-button
            href="{{route('class-specials.show-import')}}"
            css="tw-ml-2"
        >
            <i class="fas fa-file-upload"></i> Import
        </x-core.buttons.link-buttons.primary-button>
    </x-core.page-title>
    @livewire('admin.class-specials.class-specials-table')
@endsection
