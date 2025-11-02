@extends('layouts.app')

@section('content')
  <x-core.layout.info-container>
    <x-core.cards.card-with-title
      title="Import Kingdom Data"
      buttons="true"
      backUrl="{{route('items.list')}}"
    >
      <div class="mt-4 mb-4">
        <x-core.alerts.warning-alert title="ATTN!">
          <p class="mt-4 mb-4">
            Do not use this to make changes to kingdom buildings stats or
            assigned units. Only use this if you have no players in-game, or you
            have new buildings to introduce.
          </p>
          <p class="mb-4">
            Import will only assign new buildings to players kingdoms.
          </p>
          <p class="mb-4">
            If the building has a passive skill and the passive skill does not
            exist, the building will be ignored and not imported.
          </p>
        </x-core.alerts.warning-alert>
      </div>
      <form
        class="mt-4"
        action="{{ route('kingdoms.import-data') }}"
        method="POST"
        enctype="multipart/form-data"
      >
        @csrf
        <div class="form-group mb-4">
          <label class="label mb-2 block" for="kingdom_import">
            Kingdom File
          </label>
          <input
            type="file"
            class="form-control"
            id="kingdom_import"
            aria-describedby="kingdom_import"
            name="kingdom_import"
          />
        </div>
        <x-core.buttons.primary-button type="submit">
          Import Data
        </x-core.buttons.primary-button>
      </form>
    </x-core.cards.card-with-title>
  </x-core.layout.info-container>
@endsection
