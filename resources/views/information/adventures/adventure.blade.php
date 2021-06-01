@extends('layouts.information', [
    'pageTitle' => 'Adventure'
])

@section('content')
    <div class="mt-3">
        @include('admin.adventures.partials.adventure', [
            'adventure' => $adventure,
            'customUrl' => route('info.page', [
                'pageName' => 'adventure'
            ]),
        ])
    </div>
@endsection
