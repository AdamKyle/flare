@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.page-title
            title="Scheduled Events"
            route="{{route('home')}}"
            color="success"
            link="Home"
        ></x-core.page-title>

        <p class="my-4">
            Click any where in the calendar to create a new scheduled event.
            Players will be able to see this event on their event calendar.
        </p>

        <div id="event-calendar"></div>
    </x-core.layout.info-container>
@endsection
