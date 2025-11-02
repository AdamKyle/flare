@extends('layouts.app')

@section('content')
  <x-core.layout.info-container>
    <x-core.cards.card-with-title
      title="Import Raids"
      buttons="true"
      backUrl="{{route('admin.raids.list')}}"
    >
      <form
        class="mt-4"
        action="{{ route('admin.raids.import') }}"
        method="POST"
        enctype="multipart/form-data"
      >
        @csrf
        <div class="mt-4 mb-4">
          <x-core.alerts.info-alert title="Importing Tip">
            <p className="my-4">
              <strong>Caution</strong>
              If data such as bosses, monster, locations or the boss location is
              missing, the raid will be skipped.
            </p>
          </x-core.alerts.info-alert>
        </div>

        <div class="mb-5">
          <label class="label mb-2 block" for="raids">Raids File</label>
          <input id="raids" type="file" class="form-control" name="raids" />
        </div>
        <x-core.buttons.primary-button type="submit">
          Import Raids
        </x-core.buttons.primary-button>
      </form>
    </x-core.cards.card-with-title>
  </x-core.layout.info-container>
@endsection
