@extends('layouts.information', [
    'pageTitle' => 'Unit'
])

@section('content')
    <x-core.cards.card-with-title title="{{$unit->name}}" css="tw-mt-5 tw-w-full lg:tw-w-1/2 tw-m-auto">
        @include('admin.kingdoms.units.partials.unit-attributes', [
            'unit'          => $unit,
            'building'      => $building,
            'requiredLevel' => $requiredLevel,
        ])
    </x-core.cards.card-with-title>
@endsection
