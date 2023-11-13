@extends('layouts.information')

@section('content')
    <div class="mb-10 w-full lg:w-3/5 m-auto">
        <x-core.cards.card-with-title
            title="{{$location->name}}"
            buttons="true"
            backUrl="{{url()->previous()}}"
        >
            @include('admin.locations.partials.location', [
                'location' => $location,
            ])
        </x-core.cards.card-with-title>
    </div>
@endsection
