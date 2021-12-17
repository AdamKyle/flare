@extends('layouts.information', [
    'pageTitle' => 'Unit'
])

@section('content')
    <div class="mt-20 mb-10 w-full lg:w-3/5 m-auto">
        <x-core.cards.card-with-title title="{{$unit->name}}" css="mt-5 m-auto">
            @include('admin.kingdoms.units.partials.unit-attributes', [
                'unit'          => $unit,
                'building'      => $building,
                'requiredLevel' => $requiredLevel,
            ])
        </x-core.cards.card-with-title>
    </div>
@endsection
