@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-6 align-self-right">
            <h4 class="mt-2">Character Sheet</h4>
        </div>
        <div class="col-md-6 align-self-right">
            <a href="{{url()->previous()}}" class="btn btn-primary float-right ml-2">Back</a>
        </div>
    </div>
    <hr />
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="row">
                <div class="col-md-6">
                    <h4>Character Info</h4>
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <dl>
                                        <dt>Character Name:</dt>
                                        <dd>{{$character->name}}</dd>
                                        <dt>Character Race:</dt>
                                        <dd>{{$character->race->name}}</dd>
                                        <dt>Character Class:</dt>
                                        <dd>{{$character->class->name}}</dd>
                                        <dt>Character Level:</dt>
                                        <dd>{{$character->level}}</dd>
                                        <dt>Character XP:</dt>
                                        <dd>
                                            <div class="progress skill-training mb-2">
                                                <div class="progress-bar skill-bar" role="progressbar" aria-valuenow="{{$character->xp}}" aria-valuemin="0" style="width: {{$character->xp}}%;">{{$character->xp}}</div>
                                            </div>
                                        </dd>
                                    </dl>
                                </div>
        
                                <div class="col-md-4">
                                    <dl>
                                        <dt>Max Health:</dt>
                                        <dd>{{$characterInfo['maxHealth']}}</dd>
                                        <dt>Max Attack:</dt>
                                        <dd>{{$characterInfo['maxAttack']}}</dd>
                                        <dt>Max Heal For:</dt>
                                        <dd>{{$characterInfo['maxHeal']}}</dd>
                                        <dt>Max AC:</dt>
                                        <dd>{{$characterInfo['maxAC']}}</dd>
                                    </dl>
                                </div>
        
                                <div class="col-md-4">
                                    <dl>
                                        <dt>Strength:</dt>
                                        <dd>{{$character->str}} (Modded: {{round($characterInfo['str'])}})</dd>
                                        <dt>Durabillity:</dt>
                                        <dd>{{$character->dur}} (Modded: {{round($characterInfo['dur'])}})</dd>
                                        <dt>Dexterity:</dt>
                                        <dd>{{$character->dex}} (Modded: {{round($characterInfo['dex'])}})</dd>
                                        <dt>Charisma:</dt>
                                        <dd>{{$character->chr}} (Modded: {{round($characterInfo['chr'])}})</dd>
                                        <dt>Intelligence:</dt>
                                        <dd>{{$character->int}} (Modded: {{round($characterInfo['int'])}})</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <h4>Inventory</h4>
                    <div class="card">
                        <div class="card-body">
                            <dl>
                                <dt>Total gold:</dt>
                                <dd>{{$character->gold}}</dd>
                                <dt>Used / Max inventory space:</dt>
                                <dd>{{$character->inventory->slots->count()}} / {{$character->inventory_max}}</dd>
                                <dt>Stat to focus on for max damage:</dt>
                                <dd>{{$character->class->damage_stat}}</dd>
                            </dl>
                            <hr />
                            <a href="{{route('game.character.inventory')}}" class="btn btn-primary">Inventory</a>
                        </div>
                    </div>

                    <h4>Skills</h4>
                    <div class="card mt-2">
                        <div class="card-body character-skill-info">
                            @foreach($character->skills as $skill)
                                <dl>
                                    <dt><a href="{{route('skill.character.info', ['skill' => $skill->id])}}">{{$skill->name}}</a>:</dt>
                                    <dd>
                                        <div class="row">
                                            <div class="col-md-4">
                                                Level/Max: {{$skill->level}} / {{$skill->max_level}}
                                            </div>
                                            <div class="col-md-4">
                                                <div class="progress skill-training mb-2 text-center">
                                                    <div class="progress-bar skill-bar" role="progressbar" aria-valuenow="{{$skill->xp}}" aria-valuemin="0" style="width: {{$skill->xp}}%;">{{$skill->xp}}</div>
                                                </div>
                                            </div>
                                            @if ((bool) $skill->can_train)
                                                <div class="col-md-4">
                                                    <a href="#" class="btn btn-primary btn-sm mb-2 train-skill-btn" data-toggle="modal" data-target="#skill-train-{{$skill->id}}">
                                                        Train

                                                        @if ($skill->currently_training)
                                                        <i class="ml-2 fas fa-check"></i>
                                                        @endif
                                                    </a>

                                                    @if ($skill->currently_training)
                                                        <a class="btn btn-danger btn-sm mb-2 train-skill-btn" href="{{ route('logout') }}"
                                                           onclick="event.preventDefault();
                                                                    document.getElementById('cancel-skill-train-form').submit();"
                                                        >
                                                            Cancel
                                                        </a>

                                                        <i class="ml-2 fas fa-info-circle skill-info-icon text-info" 
                                                           data-toggle="tooltip" data-placement="top" 
                                                           title="Xp % Towards: {{$skill->xp_towards * 100}}%"
                                                        ></i>

                                                        <form id="cancel-skill-train-form" action="{{ route('cancel.train.skill', [
                                                            'skill' => $skill->id
                                                        ]) }}" method="POST" style="display: none;">
                                                            @csrf
                                                        </form>
                                                    @endif
                                                    @include('game.core.character.partials.skill-train-modal', ['skill' => $skill])
                                                </div>
                                            @endif
                                        </div>
                                    </dd>
                                </dl>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
