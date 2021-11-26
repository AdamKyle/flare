@extends('layouts.app')

@section('content')
  <div class="tw-mt-10 tw-mb-10 tw-w-full lg:tw-w-3/5 tw-m-auto">
    <x-core.page-title
      title="Passive Skills"
      route="{{route('home')}}"
      color="success" link="Home"
    >
      <x-core.buttons.link-buttons.primary-button
        href="{{route('passive.skills.create')}}"
        css="tw-ml-5"
      >
        Create Passive
      </x-core.buttons.link-buttons.primary-button>
    </x-core.page-title>
    <hr />
    @livewire('admin.passive-skills.data-table')
  </div>
@endsection