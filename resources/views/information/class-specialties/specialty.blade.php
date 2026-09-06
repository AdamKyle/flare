@extends('layouts.information')

@section('content')
    @include('information.class-specialties.partials.specialty', ['classSpecial' => $classSpecial])
@endsection
