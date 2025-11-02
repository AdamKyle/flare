@extends('layouts.app')

@section('content')
  <div class="tw-mt-20 tw-mb-10 tw-w-full lg:tw-w-3/5 tw-m-auto">
    <div class="tw-m-auto">
      <x-core.page.title
        title="Classes"
        route="{{route('home')}}"
        link="Home"
        color="success"
      >
        <x-core.buttons.link-buttons.primary-button
          href="{{route('classes.create')}}"
          css="tw-ml-2"
        >
          Create
        </x-core.buttons.link-buttons.primary-button>
        <x-core.buttons.link-buttons.primary-button
          href="{{route('classes.export-excel')}}"
          css="tw-ml-2"
        >
          <i class="fas fa-file-export"></i>
          Export
        </x-core.buttons.link-buttons.primary-button>
        <x-core.buttons.link-buttons.primary-button
          href="{{route('classes.import-excel')}}"
          css="tw-ml-2"
        >
          <i class="fas fa-file-upload"></i>
          Import
        </x-core.buttons.link-buttons.primary-button>
      </x-core.page.title>
    </div>
    @livewire('admin.classes.classes-table')
  </div>
@endsection
