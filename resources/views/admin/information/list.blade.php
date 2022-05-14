@extends('layouts.app')

@section('content')
    <x-core.page-title
        title="Info Pages"
        route="{{route('home')}}"
        color="success" link="Home"
    >
        <x-core.buttons.link-buttons.primary-button
            href="{{route('admin.info-management.create-page')}}"
            css="tw-ml-2"
        >
            Create Page
        </x-core.buttons.link-buttons.primary-button>
    </x-core.page-title>
    @livewire('admin.info-section.info-pages-table')
@endsection
