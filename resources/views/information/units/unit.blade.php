@extends('layouts.information', [
    'pageTitle' => 'Unit'
])

@section('content')
    <div class="row page-titles mt-3">
        <div class="col-md-6 align-self-left">
            <h4 class="mt-3">{{$unit->name}}</h4>
        </div>
        <div class="col-md-6 align-self-right">
            <a href="{{url()->previous()}}" class="btn btn-primary float-right ml-2">Back</a>
        </div>
    </div>
    <hr />
    @include('admin.kingdoms.units.partials.unit-attributes', [
        'unit'          => $unit,
        'building'      => $building,
        'requiredLevel' => $requiredLevel,
    ])
@endsection
