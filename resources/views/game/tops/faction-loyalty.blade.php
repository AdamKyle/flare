@extends('layouts.app')

@section('content')
    <div class="mr-auto ml-auto w-full max-w-7xl px-4 pb-10 sm:px-6 lg:px-8">
        <x-core.page.title
            title="Faction Loyalty Tops"
            route="{{ route('game') }}"
            link="Game"
            color="primary"
        ></x-core.page.title>
        <div id="faction-loyalty-tops" data-default-period="current_month"></div>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/tops-faction-loyalty-component.ts')
@endpush
