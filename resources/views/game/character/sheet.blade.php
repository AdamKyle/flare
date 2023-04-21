@extends('layouts.app')

@section('content')
    <x-core.page-title
        title="Character Sheet"
        route="{{route('game')}}"
        link="{{'Game'}}"
        color="{{'primary'}}"
    ></x-core.page-title>
    <hr />
    <div id="character-sheet" data-character="{{$character->id}}" data-user="{{$character->user->id}}"></div>
@endSection

@push('scripts')
    <script>
        characterSheet('character-sheet');
    </script>
@endpush
