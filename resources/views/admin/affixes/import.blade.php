@extends('layouts.app')

@section('content')
  <x-core.layout.info-container>
    <x-core.cards.card-with-title
      title="Import Affixes"
      buttons="true"
      backUrl="{{route('affixes.list')}}"
    >
      <div class="mt-4 mb-4">
        <x-core.alerts.info-alert title="ATTN!">
          If an affix affects a skill that does not exist, the affix will be
          skipped.
        </x-core.alerts.info-alert>
      </div>
      <form
        class="mt-4"
        action="{{ route('affixes.import-data') }}"
        method="POST"
        enctype="multipart/form-data"
      >
        @csrf
        <div class="mb-5">
          <label class="label mb-2 block" for="affixes_import">
            Affixes File
          </label>
          <input
            id="affixes_import"
            type="file"
            class="form-control"
            name="affixes_import"
          />
        </div>
        <x-core.buttons.primary-button type="submit">
          Import Affixes
        </x-core.buttons.primary-button>
      </form>
    </x-core.cards.card-with-title>
  </x-core.layout.info-container>
@endsection
