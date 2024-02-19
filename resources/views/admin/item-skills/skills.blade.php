@extends('layouts.app')

@section('content')
    <x-core.page-title
        title="Items Skills"
        route="{{route('home')}}"
        color="success" link="Home"
    >
        <x-core.buttons.link-buttons.primary-button
            href="{{route('admin.items-skills.create')}}"
            css="tw-ml-2"
        >
            Create Item Skill
        </x-core.buttons.link-buttons.primary-button>
        <x-core.buttons.link-buttons.primary-button
            href="{{route('admin.items-skills.export-data')}}"
            css="tw-ml-2"
        >
            <i class="fas fa-file-export"></i> Export
        </x-core.buttons.link-buttons.primary-button>
        <x-core.buttons.link-buttons.primary-button
            href="{{route('admin.items-skills.import-data')}}"
            css="tw-ml-2"
        >
            <i class="fas fa-file-upload"></i> Import
        </x-core.buttons.link-buttons.primary-button>
    </x-core.page-title>
{{--    @livewire('admin.item-skills.item-skills-table')--}}
@endsection
