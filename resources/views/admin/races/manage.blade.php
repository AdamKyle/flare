@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-6 align-self-left">
            <h4 class="mt-3">{{!is_null($race) ? 'Edit race: ' . $race->name : 'Create race'}}</h4>
        </div>
        <div class="col-md-6 align-self-right">
            <a href="{{route('home')}}" class="btn btn-success float-right ml-2">Home</a>
        </div>
    </div>
    @livewire('core.form-wizard', [
        'views' => [
            'admin.races.partials.race',
        ],
        'model'     => $race,
        'modelName' => 'race',
        'steps' => [
            'Details'
        ],
        'finishRoute' => 'races.list',
    ])
</div>
@endsection
