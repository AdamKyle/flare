@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="{{ $gameMapGemParamters->name }}"
            buttons="true"
            :back-url="route('admin.map-gems.list')"
            :edit-url="route('admin.map-gems.edit', ['gameMapGemParamters' => $gameMapGemParamters])"
        >
            @include('admin.map-gems.partials.details')
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
