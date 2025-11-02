@extends('layouts.app')

@section('content')
  <x-core.page.title
    title="Skills"
    route="{{route('home')}}"
    color="success"
    link="Home"
  >
    <x-core.buttons.link-buttons.primary-button
      href="{{route('skills.create')}}"
      css="tw-ml-2"
    >
      Create Skill
    </x-core.buttons.link-buttons.primary-button>
    <x-core.buttons.link-buttons.primary-button
      href="{{route('skills.export')}}"
      css="tw-ml-2"
    >
      <i class="fas fa-file-export"></i>
      Export
    </x-core.buttons.link-buttons.primary-button>
    <x-core.buttons.link-buttons.primary-button
      href="{{route('skills.import')}}"
      css="tw-ml-2"
    >
      <i class="fas fa-file-upload"></i>
      Import
    </x-core.buttons.link-buttons.primary-button>
  </x-core.page.title>
  @livewire('admin.skills.skills-table')
@endsection
