@extends('layouts.app')

@section('content')
  <x-core.layout.info-container>
    @include(
      'information.classes.partials.class-details',
      [
        'class' => $class,
      ]
    )
  </x-core.layout.info-container>
@endsection
