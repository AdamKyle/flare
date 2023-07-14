@extends('layouts.information')

@section('content')
    <x-core.cards.card-with-title
        title="{{$location->name}}"
        buttons="true"
        backUrl="{{url()->previous()}}"
    >
        @include('admin.locations.partials.location', [
            'location' => $location,
        ])
    </x-core.cards.card-with-title>
@endsection
