@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-6 align-self-left">
            <h4 class="mt-3">{{is_null($skill) ? 'Create Skill' : 'Edit Skill: ' . $skill->name}}</h4>
        </div>
        <div class="col-md-6 align-self-right">
            <a href="{{url()->previous()}}" class="btn btn-primary float-right ml-2">Back</a>
        </div>
    </div>
    @livewire('core.form-wizard', [
        'views' => [
            'admin.skills.partials.skill-details',
            'admin.skills.partials.skill-modifiers',
        ],
        'model'     => $skill,
        'modelName' => 'skill',
        'steps' => [
            'Skill Details',
            'Skill Modifiers',
        ],
        'finishRoute' => 'skills.list',
    ])
</div>
@endsection
