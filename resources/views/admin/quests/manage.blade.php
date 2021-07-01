@extends('layouts.app')

@section('content')

    <div class="container-fluid">
        <div class="row page-titles">
            <div class="col-md-6 align-self-right">
                <h4 class="mt-2">{{is_null($quest) ? 'Create Quest' : 'Edit Quest: ' . $quest->name}}</h4>
            </div>
            <div class="col-md-6 align-self-right">
                <a href="{{url()->previous()}}" class="btn btn-primary float-right ml-2">Back</a>
            </div>
        </div>
        @livewire('core.form-wizard', [
            'views' => [
                'admin.quests.partials.details',
            ],
            'model'     => $quest,
            'modelName' => 'quest',
            'steps' => [
                'Quest Information',
            ],
            'finishRoute' => 'quests.index',
            'editing'     => $editing,
        ])
    </div>
@endsection
