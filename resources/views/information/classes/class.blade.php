@extends('layouts.information')

@section('content')
  @include(
    'information.classes.partials.class-details',
    [
      'class' => $class,
    ]
  )
@endsection
