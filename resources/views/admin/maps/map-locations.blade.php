@extends('layouts.app')

@section('content')
    @dump($map)
    <x-core.layout.info-container>
        <x-core.cards.card-with-title title="Edit Locations for: {{ $map->name }}" buttons="true" backUrl="{{ url()->previous() }}">
            Show map here.
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
