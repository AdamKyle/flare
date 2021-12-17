@extends('layouts.information')

@section('content')
  <div class="prose dark:prose-dark max-w-7xl mx-auto mb-20">
    @foreach($sections as $section)
      <x-core.cards.card css="mt-5 m-auto">
        @markdown($section['content'])
      </x-core.cards.card>

      @if (!is_null($section['view']))
        @if ($section['livewire'])
          @if ($section['before'])
            <div class="mb-2 mt-2">
              @include($section['before'])
            </div>
          @endif

          <div class="mt-5 m-auto">
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
