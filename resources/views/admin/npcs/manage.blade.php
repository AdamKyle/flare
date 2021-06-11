@extends('layouts.app')

@section('content')

    <div class="container-fluid">
        <div class="row page-titles">
            <div class="col-md-6 align-self-right">
                <h4 class="mt-2">{{is_null($npc) ? 'Create NPC' : 'Edit NPC: ' . $npc->name}}</h4>
            </div>
            <div class="col-md-6 align-self-right">
                <a href="{{url()->previous()}}" class="btn btn-primary float-right ml-2">Back</a>
            </div>
        </div>
        @livewire('core.form-wizard', [
            'views' => [
                'admin.npcs.partials.details',
                'admin.npcs.partials.commands',
            ],
            'model'     => $npc,
            'modelName' => 'npc',
            'steps' => [
                'NPC Details',
                'NPC Commands',
            ],
            'finishRoute' => 'npcs.index',
            'editing'     => $editing,
        ])
    </div>
@endsection
