@extends('layouts.app')

@section('content')
  <x-core.layout.info-container>
    <x-core.cards.card-with-title
      title="Export Kingdom Data"
      buttons="true"
      backUrl="{{route('home')}}"
    >
      <x-core.alerts.warning-alert title="ATTN!">
        <p class="mt-4 mb-4">
          Export both buildings, units and the building -> unit relationships.
          It is not suggested you make edits to these excell files.
        </p>
      </x-core.alerts.warning-alert>
      <form
        method="POST"
        action="{{ route('kingdoms.export-data') }}"
        class="mb-4 text-center"
      >
        @csrf
        <x-core.buttons.primary-button type="submit">
          Export Kingdom Data
        </x-core.buttons.primary-button>
      </form>
    </x-core.cards.card-with-title>
  </x-core.layout.info-container>
@endsection
