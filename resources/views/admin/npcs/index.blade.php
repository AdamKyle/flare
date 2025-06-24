@extends('layouts.app')

@section('content')
    <x-core.page.title
        title="NPC's"
        route="{{route('home')}}"
        color="success"
        link="Home"
    >
        <x-core.buttons.link-buttons.primary-button
            href="{{route('npcs.create')}}"
            css="tw-ml-2"
        >
            Create NPC
        </x-core.buttons.link-buttons.primary-button>
        <x-core.buttons.link-buttons.primary-button
            href="{{route('npcs.export')}}"
            css="tw-ml-2"
        >
            <i class="fas fa-file-export"></i>
            Export
        </x-core.buttons.link-buttons.primary-button>
        <x-core.buttons.link-buttons.primary-button
            href="{{route('npcs.import')}}"
            css="tw-ml-2"
        >
            <i class="fas fa-file-upload"></i>
            Import
        </x-core.buttons.link-buttons.primary-button>
    </x-core.page.title>
    @livewire('admin.npcs.npc-table')
@endsection
