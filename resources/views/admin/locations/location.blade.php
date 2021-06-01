@extends('layouts.app')

@section('content')
    @include('admin.locations.partials.location', [
        'location' => $location
    ])
@endsection
