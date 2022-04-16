@extends('layouts.app')

@section('content')
    @dump([
        'item'      => $item,
        'effects'   => $effects,
        'monster'   => $monster,
        'quest'     => $quest,
        'location'  => $location,
        'adventure' => $adventure,
        'skills'    => $skills,
        'skill'     => $skill,
    ])
    <x-core.layout.info-container>
        @include('game.items.components.item-data', ['item' => $item])
    </x-core.layout.info-container>
@endsection
