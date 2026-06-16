@extends('layouts.app')

@section('content')
    <x-core.page-title title="Map Gems" route="{{ route('home') }}" color="success" link="Home">
        <x-core.buttons.link-buttons.primary-button href="{{ route('admin.map-gems.create') }}">Create Map Gem</x-core.buttons.link-buttons.primary-button>
        <x-core.buttons.link-buttons.primary-button href="{{ route('admin.map-gems.export-data') }}">Export</x-core.buttons.link-buttons.primary-button>
        <x-core.buttons.link-buttons.primary-button href="{{ route('admin.map-gems.import-data') }}">Import</x-core.buttons.link-buttons.primary-button>
    </x-core.page-title>

    @livewire('info.map-gems')
@endsection
