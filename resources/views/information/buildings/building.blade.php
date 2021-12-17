@extends('layouts.information', [
    'pageTitle' => 'Unit'
])

@section('content')
  <div class="mt-20 mb-10 w-full lg:w-5/6 m-auto">
    @include('admin.kingdoms.buildings.partials.building-details');
  </div>
@endsection
