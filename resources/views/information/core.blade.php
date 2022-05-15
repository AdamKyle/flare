@extends('layouts.information')

@section('content')
  <x-core.layout.info-container>
      <x-core.page-title
          title="{{$pageTitle}}"
          route="{{url()->previous()}}"
          color="success" link="back"
      >
          @auth
              @if (auth()->user()->hasRole('Admin'))
                  <x-core.buttons.link-buttons.primary-button
                      href="{{route('admin.info-management.up-page', ['infoPage' => $pageId,])}}"
                      css="tw-ml-2"
                  >
                      Edit Page
                  </x-core.buttons.link-buttons.primary-button>
              @endif
          @endauth
      </x-core.page-title>

      <div class="my-5">
          <x-core.alerts.simple-info-alert>
              All images can be clicked on to be made larger.
          </x-core.alerts.simple-info-alert>
      </div>

      <div class="prose dark:prose min-w-full m-auto">
          @foreach($sections as $section)

              @if (is_null($section['content_image_path']))
                  <div class="mt-[30px]">
                      <x-core.cards.card>
                          {!! $section['content'] !!}
                      </x-core.cards.card>
                  </div>
              @else
                  <div class="grid grid-cols-2 gap-4 m-auto">
                      <div class="mt-[30px]">
                          <x-core.cards.card>
                             {!! $section['content'] !!}
                          </x-core.cards.card>
                      </div>

                      <img src="{{Storage::disk('info-sections-images')->url($section['content_image_path'])}}" class="rounded-sm p-1 bg-white border max-w-[475px] cursor-pointer glightbox mt-[30px]" alt="image"/>
                  </div>
              @endif

              @if (!is_null($section['live_wire_component']))
                  <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>

                  @livewire($section['live_wire_component'])
              @endif

              <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
          @endforeach
      </div>
  </x-core.layout.info-container>
@endsection
