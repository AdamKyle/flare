@extends('layouts.information', [
    'pageTitle' => $pageTitle
])

@section('content')
    <div class="row justify-content-center mt-5">
        <div class="col-md-12 align-self-left">
            <h2 class="mt-2">Page Title</h2>
        </div>
    </div>
    <hr />
    <div class="mt-5">
        @foreach($sections as $section)
            <div class="row justify-content-center mb-2 mt-3">
                <div class="col-xl-12">
                    @markdown($section['content'])
                </div>
            </div>

            @if (!is_null($section['view']))
                @if ($section['livewire'])
                    true
                @else
                    false
                @endif
            @endif
        @endforeach
    </div>
@endsection
