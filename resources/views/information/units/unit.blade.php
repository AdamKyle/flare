@extends('layouts.information', [
    'pageTitle' => 'Unit'
])

@section('content')
    <div class="tw-mt-20 tw-mb-10 tw-w-full lg:tw-w-3/5 tw-m-auto">
        <x-core.cards.card-with-title title="{{$unit->name}}" css="tw-mt-5 tw-m-auto">
            @include('admin.kingdoms.units.partials.unit-attributes', [
                'unit'          => $unit,
                'building'      => $building,
                'requiredLevel' => $requiredLevel,
            ])
        </x-core.cards.card-with-title>
    </div>
@endsection
