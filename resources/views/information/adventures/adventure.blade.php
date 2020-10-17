@extends('layouts.information', [
    'pageTitle' => $pageName
])

@section('content')
    <div class="mt-5">
        @include('admin.adventures.adventure', [
            'adventure' => $adventure,
            'customClass' => 'mt-5'
        ])
    </div>
@endsection
