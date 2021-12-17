@extends('layouts.information', [
    'pageTitle' => 'Adventure'
])

@section('content')
    <div class="w-full lg:w-3/5 m-auto mt-20 mb-10">
        @include('admin.adventures.partials.adventure', [
            'adventure' => $adventure,
            'customUrl' => route('info.page', [
                'pageName' => 'adventure'
            ]),
        ])
    </div>
@endsection
