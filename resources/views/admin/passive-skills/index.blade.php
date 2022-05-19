@extends('layouts.app')

@section('content')
    <x-core.page-title
        title="Passive Skills"
        route="{{route('home')}}"
        color="success" link="Home"
    >
        <x-core.buttons.link-buttons.primary-button
            href="{{route('passive.skills.create')}}"
            css="ml-5"
        >
            Create Passive
        </x-core.buttons.link-buttons.primary-button>
        <x-core.buttons.link-buttons.primary-button
            href="{{route('passive.skills.export')}}"
            css="tw-ml-2"
        >
            <i class="fas fa-file-export"></i> Export
        </x-core.buttons.link-buttons.primary-button>
        <x-core.buttons.link-buttons.primary-button
            href="{{route('passive.skills.import')}}"
            css="tw-ml-2"
        >
            <i class="fas fa-file-upload"></i> Import
        </x-core.buttons.link-buttons.primary-button>
    </x-core.page-title>

    @livewire('admin.passive-skills.passive-skill-table')
@endsection
