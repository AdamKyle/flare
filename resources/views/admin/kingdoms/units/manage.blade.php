@extends('layouts.app')

@section('content')
    <div class="row page-titles">
        <div class="col-md-6 align-self-left">
            <h4 class="mt-3">{{!is_null($unit) ? 'Edit Unit: ' . $unit->name : 'Create unit'}}</h4>
        </div>
        <div class="col-md-6 align-self-right">
            <a href="{{route('home')}}" class="btn btn-success float-right ml-2">Home</a>
            <a href="{{route('units.list')}}" class="btn btn-primary float-right ml-2">Back</a>
        </div>
    </div>
    @livewire('core.form-wizard', [
        'views' => [
            'admin.kingdoms.units.partials.details',
        ],
        'model'     => $unit,
        'modelName' => 'gameUnit',
        'steps' => [
            'Unit Details',
        ],
        'finishRoute' => 'units.list',
        'editing'     => $editing,
    ])
@endsection
