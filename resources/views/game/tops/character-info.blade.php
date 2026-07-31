@extends('layouts.app')

@section('content')
    <div class="w-full px-4 sm:px-6 lg:px-8 pb-10">
        <x-core.page-title
            title="{{$character->name}}"
            route="{{route('game.tops')}}"
            link="Tops"
            color="primary"
        ></x-core.page-title>

        <div
            id="character-tops"
            data-selected-character-id="{{$character->id}}"
            data-default-period="current_month"
        ></div>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/tops-character-component.ts')
@endpush
