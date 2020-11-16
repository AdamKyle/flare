@extends('layouts.app')

@section('content')
    <div class="row page-titles">
        <div class="col-md-6 align-self-left">
            <h4 class="mt-3">{{!is_null($class) ? 'Edit class: ' . $class->name : 'Create class'}}</h4>
        </div>
        <div class="col-md-6 align-self-right">
            <a href="{{route('home')}}" class="btn btn-success float-right ml-2">Home</a>
        </div>
    </div>
    @livewire('core.form-wizard', [
        'views' => [
            'admin.classes.partials.game-class',
        ],
        'model'     => $class,
        'modelName' => 'gameClass',
        'steps' => [
            'Details'
        ],
        'finishRoute' => 'classes.list',
    ])
@endsection
