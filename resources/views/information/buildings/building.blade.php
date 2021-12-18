@extends('layouts.information', [
    'pageTitle' => 'Unit'
])

@section('content')
  <div class="tw-mt-20 tw-mb-10">
    @include('admin.kingdoms.buildings.partials.building-details')
  </div>
@endsection
