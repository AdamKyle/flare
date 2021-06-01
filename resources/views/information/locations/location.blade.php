@extends('layouts.information', [
    'pageTitle' => 'Location'
])

@section('content')
    <div class="mt-3">
        @include('admin.locations.partials.location', [
            'location' => $location,
        ])
    </div>
@endsection
