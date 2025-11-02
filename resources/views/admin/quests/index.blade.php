@extends('layouts.app')

@section('content')
  <x-core.page.title
    title="Quests"
    route="{{route('home')}}"
    color="success"
    link="Home"
  >
    <x-core.buttons.link-buttons.primary-button
      href="{{route('quests.create')}}"
      css="tw-ml-2"
    >
      Create Quest
    </x-core.buttons.link-buttons.primary-button>
    <x-core.buttons.link-buttons.primary-button
      href="{{route('quests.export')}}"
      css="tw-ml-2"
    >
      <i class="fas fa-file-export"></i>
      Export
    </x-core.buttons.link-buttons.primary-button>
    <x-core.buttons.link-buttons.primary-button
      href="{{route('quests.import')}}"
      css="tw-ml-2"
    >
      <i class="fas fa-file-upload"></i>
      Import
    </x-core.buttons.link-buttons.primary-button>
  </x-core.page.title>
  @livewire('admin.quests.quests-table')
@endsection
