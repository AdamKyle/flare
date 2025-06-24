@extends('layouts.app')

@section('content')
    <x-core.page.title
        title="Kingdom Buildings"
        route="{{route('home')}}"
        color="success"
        link="Home"
    >
        <x-core.buttons.link-buttons.primary-button
            href="{{route('buildings.create')}}"
            css="tw-ml-2"
        >
            Create Building
        </x-core.buttons.link-buttons.primary-button>
    </x-core.page.title>
    @livewire('admin.kingdoms.buildings.buildings-table')
@endsection
