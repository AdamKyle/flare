@extends('layouts.information')

@section('content')
    <div class="tw-mt-20 tw-mb-10 tw-w-full lg:tw-w-3/5 tw-m-auto">
        @include('admin.locations.partials.location', [
            'location' => $location,
        ])
    </div>
@endsection
