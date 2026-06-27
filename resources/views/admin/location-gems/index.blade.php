@extends('layouts.app')

@section('content')
    <x-core.page-title title="Location Gems" route="{{ route('home') }}" color="success" link="Home">
        <x-core.buttons.link-buttons.primary-button href="{{ route('admin.location-gems.create') }}">Create Location Gem</x-core.buttons.link-buttons.primary-button>
        <x-core.buttons.link-buttons.primary-button href="{{ route('admin.location-gems.export-data') }}">Export</x-core.buttons.link-buttons.primary-button>
        <x-core.buttons.link-buttons.primary-button href="{{ route('admin.location-gems.import-data') }}">Import</x-core.buttons.link-buttons.primary-button>
    </x-core.page-title>

    @livewire('info.location-gems')
@endsection
