@extends('layouts.information', [
    'pageTitle' => 'Unit'
])

@section('content')
  <div class="tw-mt-20 tw-mb-10 tw-w-full lg:tw-w-5/6 tw-m-auto">
    @include('admin.kingdoms.buildings.partials.building-details');
  </div>
@endsection
