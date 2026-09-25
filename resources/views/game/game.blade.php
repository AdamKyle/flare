@extends('layouts.game')

@section('content')
    <div id="game-launcher"></div>
@endsection

@push('game-app')
    @vite('resources/js/game.ts')
@endpush
