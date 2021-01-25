@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-6 align-self-right">
            <h4 class="mt-2">{{is_null($location) ? 'Create Location' : 'Edit Location: ' . $location->name}}</h4>
        </div>
        <div class="col-md-6 align-self-right">
            <a href="{{route('home')}}" class="btn btn-success float-right ml-2">Home</a>
            <a href="{{route('locations.list')}}" class="btn btn-primary float-right ml-2">Back</a>
        </div>
    </div>
    @livewire('core.form-wizard', [
        'views' => [
            'admin.locations.partials.details',
            'admin.locations.partials.quest-item',
        ],
        'model'     => $location,
        'modelName' => 'location',
        'steps' => [
            'Location Details',
            'Location Quest Item',
        ],
        'finishRoute' => 'locations.list',
        'editing'     => $editing,
    ])
</div>
@endsection
