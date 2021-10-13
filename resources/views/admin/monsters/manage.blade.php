@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-6 align-self-right">
            <h4 class="mt-2">{{is_null($monster) ? 'Create Monster' : 'Edit Monster: ' . $monster->name}}</h4>
        </div>
        <div class="col-md-6 align-self-right">
            <a href="{{url()->previous()}}" class="btn btn-primary float-right ml-2">Back</a>
        </div>
    </div>
    @livewire('core.form-wizard', [
        'views' => [
            'admin.monsters.partials.stats',
            'admin.monsters.partials.quest-item',
        ],
        'model'     => $monster,
        'modelName' => 'monster',
        'steps' => [
            'Monster Base Stats',
            'Monster Quest Item Reward'
        ],
        'finishRoute' => 'monsters.list',
        'editing'     => $editing,
    ])
</div>
@endsection
