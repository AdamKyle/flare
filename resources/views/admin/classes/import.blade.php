@extends('layouts.app')

@section('content')
  <x-core.layout.info-container>
    <x-core.cards.card-with-title
      title="Import Classes"
      buttons="true"
      backUrl="{{route('classes.list')}}"
    >
      <form
        class="mt-4"
        action="{{ route('classes.import-data') }}"
        method="POST"
        enctype="multipart/form-data"
      >
        @csrf
        <div class="mb-5">
          <label class="label mb-2 block" for="classes_import">
            Classes File
          </label>
          <input
            id="classes_import"
            type="file"
            class="form-control"
            name="classes_import"
          />
        </div>
        <x-core.buttons.primary-button type="submit">
          Import Classes
        </x-core.buttons.primary-button>
      </form>
    </x-core.cards.card-with-title>
  </x-core.layout.info-container>
@endsection
