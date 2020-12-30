@extends('layouts.app')

@section('content')
    <div class="row page-titles">
        <div class="col-md-6 align-self-left">
            <h4 class="mt-3">{{!is_null($building) ? 'Edit Building: ' . $building->name : 'Create building'}}</h4>
        </div>
        <div class="col-md-6 align-self-right">
            <a href="{{route('home')}}" class="btn btn-success float-right ml-2">Home</a>
            <a href="{{route('buildings.list')}}" class="btn btn-primary float-right ml-2">Back</a>
        </div>
    </div>
    @livewire('core.form-wizard', [
        'views' => [
            'admin.kingdoms.buildings.partials.details',
            'admin.kingdoms.buildings.partials.attributes',
        ],
        'model'     => $building,
        'modelName' => 'gameBuilding',
        'steps' => [
            'Building Details',
            'Building Attributes',
        ],
        'finishRoute' => 'buildings.list',
    ])
@endsection
