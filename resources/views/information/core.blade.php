@extends('layouts.information')

@section('content')
  <div class="tw-mt-20 tw-mb-10">
    @foreach($sections as $section)
      <x-core.cards.card css="tw-mt-5 tw-w-full lg:tw-w-3/4 tw-m-auto">
        @markdown($section['content'])
      </x-core.cards.card>

      @if (!is_null($section['view']))
        @if ($section['livewire'])
          @if ($section['before'])
            <div class="tw-mb-2 tw-mt-2">
              @include($section['before'])
            </div>
          @endif

          <div class="tw-mt-5 tw-m-auto">
            @livewire($section['view'], [
                'only'          => $section['only'],
                'showSkillInfo' => $section['showSkillInfo'],
                'showDropDown'  => $section['showDropDown'],
                'type'          => $section['type'],
                'craftOnly'     => $section['craftOnly'],
            ])
          </div>
        @elseif ($section['view'])
          @include($section['view'], $section['view_attributes'])
        @endif
      @endif
    @endforeach
  </div>
@endsection
