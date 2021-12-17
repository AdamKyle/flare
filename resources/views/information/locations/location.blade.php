@extends('layouts.information')

@section('content')
    <div class="mt-20 mb-10 w-full lg:w-3/5 m-auto">
        @include('admin.locations.partials.location', [
            'location' => $location,
        ])
    </div>
@endsection
