@extends('layouts.app')

@section('content')
    <div class="mr-auto ml-auto w-full max-w-7xl px-4 pb-10 sm:px-6 lg:px-8">
        <x-core.page.title
            title="Kingdom Tops"
            route="{{ route('game') }}"
            link="Game"
            color="primary"
        ></x-core.page.title>
        <div id="kingdom-tops" data-default-period="current_month"></div>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/tops-kingdoms-component.ts')
@endpush
