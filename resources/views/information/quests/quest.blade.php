@extends('layouts.information')

@section('content')
    <x-core.layout.info-container>
        <x-core.page.title
            title="{{ $quest->name }}"
            route="{{ url()->previous() }}"
            link="Back"
            color="primary"
        ></x-core.page.title>

        <div class="m-auto" id="quest-info-app" data-quest-id="{{ $quest->id }}"></div>
    </x-core.layout.info-container>
@endsection

@push('scripts')
    @vite('resources/js/information/quests/quests-info-app.tsx')
@endpush
