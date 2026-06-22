@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="{{ $gameMapGemParamter->name }}"
            buttons="true"
            :back-url="route('info.page.map-gems.list')"
            :edit-url="route('admin.map-gems.edit', ['gameMapGemParamter' => $gameMapGemParamter])"
        >
            @include('information.map-gems.partials.details')
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
