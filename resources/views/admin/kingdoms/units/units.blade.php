@extends('layouts.app')

@section('content')
  <x-core.page.title
    title="Kingdom Units"
    route="{{route('home')}}"
    color="success"
    link="Home"
  >
    <x-core.buttons.link-buttons.primary-button
      href="{{route('units.create')}}"
      css="tw-ml-2"
    >
      Create Unit
    </x-core.buttons.link-buttons.primary-button>
  </x-core.page.title>
  @livewire('admin.kingdoms.units.units-table')
@endsection
