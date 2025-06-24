@extends('layouts.app')

@section('content')
    <x-core.page.title
        title="Monsters & Celestials"
        route="{{route('home')}}"
        color="success"
        link="Home"
    >
        <x-core.buttons.link-buttons.primary-button
            href="{{route('monsters.create')}}"
            css="tw-ml-2"
        >
            Create Monster
        </x-core.buttons.link-buttons.primary-button>
        <x-core.buttons.link-buttons.primary-button
            href="{{route('monsters.export')}}"
            css="tw-ml-2"
        >
            <i class="fas fa-file-export"></i>
            Export
        </x-core.buttons.link-buttons.primary-button>
        <x-core.buttons.link-buttons.primary-button
            href="{{route('monsters.import')}}"
            css="tw-ml-2"
        >
            <i class="fas fa-file-upload"></i>
            Import
        </x-core.buttons.link-buttons.primary-button>
    </x-core.page.title>

    @livewire('admin.monsters.monsters-table')
@endsection
