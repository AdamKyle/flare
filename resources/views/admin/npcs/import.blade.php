@extends('layouts.app')

@section('content')
  <x-core.layout.info-container>
    <x-core.cards.card-with-title
      title="Import NPCs"
      buttons="true"
      backUrl="{{route('npcs.index')}}"
    >
      <form
        class="mt-4"
        action="{{ route('npcs.import-data') }}"
        method="POST"
        enctype="multipart/form-data"
      >
        @csrf
        <div class="mb-5">
          <label class="label mb-2 block" for="guide_quests_import">
            Npc's File
          </label>
          <input
            id="npcs_import"
            type="file"
            class="form-control"
            name="npcs_import"
          />
        </div>
        <x-core.buttons.primary-button type="submit">
          Import NPCs
        </x-core.buttons.primary-button>
      </form>
    </x-core.cards.card-with-title>
  </x-core.layout.info-container>
@endsection
