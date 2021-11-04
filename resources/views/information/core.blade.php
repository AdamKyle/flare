@extends('layouts.information', [
    'pageTitle' => $pageTitle
])

@section('content')
    <div class="mt-5">
        @foreach($sections as $section)
            <div class="row justify-content-center mb-2 mt-3 text-lg">
                <div class="col-xl-12">
                    <x-cards.card>
                    @markdown($section['content'])
                    </x-cards.card>
                </div>
            </div>

            @if (!is_null($section['view']))
                @if ($section['livewire'])
                    @if ($section['before'])
                        <div class="mb-2 mt-2">
                            @include($section['before'])
                        </div>
                    @endif

                <div class="mb-3 mt-3">
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
