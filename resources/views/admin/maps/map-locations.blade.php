@extends('layouts.app')

@section('content')
  <x-core.layout.info-container>
    <x-core.cards.card-with-title
      title="Edit Locations for: {{ $map->name }}"
      buttons="true"
      backUrl="{{ url()->previous() }}"
    >
      <div id="map-manager" data-map-id="{{ $map->id }}"></div>
    </x-core.cards.card-with-title>
  </x-core.layout.info-container>
@endsection
