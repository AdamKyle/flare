@extends('layouts.app')

@section('content')
    <div class="w-full max-w-7xl ml-auto mr-auto px-4 sm:px-6 lg:px-8 pb-10">
        <x-core.page-title title="Kingdom Tops" route="{{route('game')}}" link="Game" color="primary"></x-core.page-title>
        <div id="kingdom-tops" data-default-period="current_month"></div>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/tops-kingdoms-component.ts')
@endpush
