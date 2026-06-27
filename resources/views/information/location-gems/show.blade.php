@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="{{ $gameLocationGemParamter->name }}"
            buttons="true"
            :back-url="route('info.page.location-gems.list')"
            :edit-url="route('admin.location-gems.edit', ['gameLocationGemParamter' => $gameLocationGemParamter])"
        >
            @include('information.location-gems.partials.details')
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
