@extends('layouts.app')

@section('content')
    <x-core.page-title
        title="Affixes"
        route="{{route('home')}}"
        color="success"
        link="Home"
    >
        <x-core.buttons.link-buttons.primary-button
            href="{{route('affixes.create')}}"
            css="tw-ml-2"
        >
            Create Affix
        </x-core.buttons.link-buttons.primary-button>
        <x-core.buttons.link-buttons.primary-button
            href="{{route('affixes.export')}}"
            css="tw-ml-2"
        >
            <i class="fas fa-file-export"></i>
            Export
        </x-core.buttons.link-buttons.primary-button>
        <x-core.buttons.link-buttons.primary-button
            href="{{route('affixes.import')}}"
            css="tw-ml-2"
        >
            <i class="fas fa-file-upload"></i>
            Import
        </x-core.buttons.link-buttons.primary-button>
    </x-core.page-title>
    @livewire('admin.affixes.affixes-table')
@endsection
