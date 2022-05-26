@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        @php
            $backUrl = route('locations.list');

            if (!auth()->user()->hasRole('Admin')) {
                $backUrl = '/information/locations';
            }
        @endphp

        <x-core.cards.card-with-title
            title="{{$location->name}}"
            buttons="true"
            backUrl="{{$backUrl}}"
            editUrl="{{route('location.edit', ['location' => $location->id])}}"
        >
            @include('admin.locations.partials.location', [
                'location' => $location
            ])
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
