@extends('layouts.app')

@section('content')
    <div class="tw-mt-20 tw-mb-10 tw-w-full lg:tw-w-3/5 tw-m-auto">
        <div class="tw-m-auto">
            <x-core.page-title
              title="Raids"
              route="{{route('home')}}"
              link="Home"
              color="success"
            >
                <x-core.buttons.link-buttons.primary-button href="{{route('admin.raids.create')}}" css="tw-ml-2">
                    Create
                </x-core.buttons.link-buttons.primary-button>
                <x-core.buttons.link-buttons.primary-button
                    href="{{route('admin.raids.export-data')}}"
                    css="tw-ml-2"
                >
                    <i class="fas fa-file-export"></i> Export
                </x-core.buttons.link-buttons.primary-button>
                <x-core.buttons.link-buttons.primary-button
                    href="{{route('admin.raids.import-data')}}"
                    css="tw-ml-2"
                >
                    <i class="fas fa-file-upload"></i> Import
                </x-core.buttons.link-buttons.primary-button>
            </x-core.page-title>
        </div>
        <x-core.alerts.info-alert>
            <strong>ATTN!</strong> Raids must be scheduled on the <a href="{{route('admin.events')}}" target="_blank">  <i class="fas fa-external-link-alt"></i> Event Page</a>.
        </x-core.alerts.info-alert>
        @livewire('admin.raids.raids-table')
    </div>
@endsection
