@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.page-title
        title="Scheduled Events"
        route="{{route('home')}}"
        color="success" link="Home"
      >
          <x-core.buttons.link-buttons.primary-button
            href="{{route('admin.events.export-data')}}"
            css="tw-ml-2"
          >
              <i class="fas fa-file-export"></i> Export
          </x-core.buttons.link-buttons.primary-button>
          <x-core.buttons.link-buttons.primary-button
            href="{{route('admin.events.import-data')}}"
            css="tw-ml-2"
          >
              <i class="fas fa-file-upload"></i> Import
          </x-core.buttons.link-buttons.primary-button>
      </x-core.page-title>

      <div id="event-calendar"></div>
    </x-core.layout.info-container>
@endsection
