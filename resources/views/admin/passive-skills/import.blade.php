@extends('layouts.app')

@section('content')
  <div class="tw-mt-10 tw-mb-10 tw-w-full lg:tw-w-3/5 tw-m-auto">
    <x-core.page-title
      title="Import Passive Skill Data"
      route="{{route('home')}}"
      color="success"
      link="Home"
    >
    </x-core.page-title>

    <x-core.cards.card>
      <div class="mt-4">
        <form class="mt-4" action="{{route('passive.skills.import-data')}}" method="POST" enctype="multipart/form-data">
          @csrf
          <div class="form-group">
            <label for="passives_import">Passive Skills File</label>
            <input type="file" class="form-control" id="passives_import" aria-describedby="passives_import" name="passives_import">
          </div>
          <button type="submit" class="btn btn-primary">Import</button>
        </form>
      </div>
    </x-core.cards.card>
  </div>
@endsection
