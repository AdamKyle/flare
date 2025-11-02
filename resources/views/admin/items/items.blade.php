@extends('layouts.app')

@section('content')
  <div class="mx-auto my-4 w-full px-4 md:w-3/4">
    <x-core.page.title
      title="Items"
      route="{{route('home')}}"
      color="success"
      link="Home"
    >
      <x-core.buttons.link-buttons.primary-button
        href="{{route('items.create')}}"
        css="tw-ml-2"
      >
        Create Item
      </x-core.buttons.link-buttons.primary-button>
      <x-core.buttons.link-buttons.primary-button
        href="{{route('items.export')}}"
        css="tw-ml-2"
      >
        <i class="fas fa-file-export"></i>
        Export
      </x-core.buttons.link-buttons.primary-button>
      <x-core.buttons.link-buttons.primary-button
        href="{{route('items.import')}}"
        css="tw-ml-2"
      >
        <i class="fas fa-file-upload"></i>
        Import
      </x-core.buttons.link-buttons.primary-button>
    </x-core.page.title>

    @livewire('admin.items.items-table')
  </div>

@endsection
