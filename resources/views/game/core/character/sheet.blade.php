@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Character Sheet</h4>
        
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
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Inventory</h4>

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

                    <div class="card mt-2">
                        <div class="card-body">
                            <h4 class="card-title">Skills</h4>
                            @foreach($character->skills as $skill)
                                <dl>
                                    <dt><a href="{{route('skill.character.info', ['skill' => $skill->id])}}">{{$skill->name}}</a>:</dt>
                                    <dd>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="progress skill-training mb-2 text-center">
                                                    <div class="progress-bar skill-bar" role="progressbar" aria-valuenow="{{$skill->level}}" aria-valuemin="0" style="width: {{$skill->level}}%;">{{$skill->level}}</div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <a href="#" class="btn btn-primary btn-sm mb-2 train-skill-btn" data-toggle="modal" data-target="#skill-train-{{$skill->id}}">
                                                    Train

                                                    @if ($skill->currently_training)
                                                    <i class="ml-2 fas fa-check"></i>
                                                    @endif
                                                </a>
                                                @include('game.core.character.partials.skill-train-modal', ['skill' => $skill])
                                            </div>
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
