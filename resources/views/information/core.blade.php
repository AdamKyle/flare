@extends('layouts.information', [
    'pageTitle' => $pageTitle
])

@section('content')
    <div class="mt-5">
        @foreach($sections as $section)
            <div class="row justify-content-center mb-2 mt-3 text-lg">
                <div class="col-xl-12">
                    @markdown($section['content'])
                </div>
            </div>

            @if (!is_null($section['view']))
                @if ($section['livewire'])
                    @livewire($section['view'])
                @else
                    false
                @endif
            @endif

        @endforeach
    </div>
@endsection
