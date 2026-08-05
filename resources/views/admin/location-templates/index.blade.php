@extends('layouts.admin')

@section('content')
    <x-core.page.title title="Location Templates" route="{{ route('home') }}" color="success" link="Home">
        <x-core.buttons.link-buttons.primary-button href="{{ route('admin.location-templates.create') }}">Create Location Template</x-core.buttons.link-buttons.primary-button>
        <x-core.buttons.link-buttons.primary-button href="{{ route('admin.location-templates.export-data') }}">Export</x-core.buttons.link-buttons.primary-button>
        <x-core.buttons.link-buttons.primary-button href="{{ route('admin.location-templates.import-data') }}">Import</x-core.buttons.link-buttons.primary-button>
    </x-core.page.title>

    @livewire('admin.location-templates.location-templates-table')
@endsection
