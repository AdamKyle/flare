@extends('layouts.app')

@section('content')
    @include('admin.adventures.partials.adventure', [
        'adventure' => $adventure
    ])
@endsection
