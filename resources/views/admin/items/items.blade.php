@extends('layouts.app')

@section('content')
<<<<<<< HEAD
    <div class="w-full lg:w-3/4 m-auto">
        <div class="row page-titles">
            <div class="col-md-6 align-self-left">
                <h4 class="mt-3">Items</h4>
            </div>
            <div class="col-md-6 align-self-right">
                <a href="{{route('home')}}" class="btn btn-success float-right ml-2">Home</a>
                <a href="{{route('items.create')}}" class="btn btn-primary float-right ml-2">Create</a>
            </div>
        </div>
=======
    <div class="tw-mt-10 tw-mb-10 tw-w-full lg:tw-w-3/5 tw-m-auto">
        <x-core.page-title
          title="Items"
          route="{{route('home')}}"
          color="success" link="Home"
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
                <i class="fas fa-file-export"></i> Export
            </x-core.buttons.link-buttons.primary-button>
            <x-core.buttons.link-buttons.primary-button
              href="{{route('items.import')}}"
              css="tw-ml-2"
            >
                <i class="fas fa-file-upload"></i> Import
            </x-core.buttons.link-buttons.primary-button>
        </x-core.page-title>
>>>>>>> 1.1.10
        @livewire('admin.items.data-table', [
          'showSkillInfo' => true
        ])
    </div>
@endsection
