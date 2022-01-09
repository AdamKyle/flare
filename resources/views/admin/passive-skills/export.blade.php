@extends('layouts.app')

@section('content')
  <div class="tw-mt-10 tw-mb-10 tw-w-full lg:tw-w-3/5 tw-m-auto">
    <x-core.page-title
      title="Export Passive Skill Data"
      route="{{route('home')}}"
      color="success"
      link="Home"
    >
    </x-core.page-title>

    <x-cards.card>
      <div class="text-center mt-4">
        <form method="POST" action="{{ route('passive.skills.export-data') }}">
          @csrf
          <button type="submit" class="btn btn-primary">Export</button>
        </form>
      </div>
    </x-cards.card>
  </div>
@endsection
