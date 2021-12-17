@extends('layouts.app')

@section('content')
  <div class="mt-10 mb-10 w-full lg:w-3/5 m-auto">
    <x-core.page-title
      title="Passive Skills"
      route="{{route('home')}}"
      color="success" link="Home"
    >
      <x-core.buttons.link-buttons.primary-button
        href="{{route('passive.skills.create')}}"
        css="ml-5"
      >
        Create Passive
      </x-core.buttons.link-buttons.primary-button>
    </x-core.page-title>
    <hr />
    @livewire('admin.passive-skills.data-table')
  </div>
@endsection