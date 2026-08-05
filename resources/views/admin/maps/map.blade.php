@extends('layouts.admin')

@section('content')
  @include(
    'admin.maps.partials.map-details',
    [
      'map' => $map,
      'mapUrl' => $mapUrl,
      'itemNeeded' => $itemNeeded,
    ]
  )
@endsection
