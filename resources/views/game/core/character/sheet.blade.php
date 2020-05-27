@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Character Sheet</h4>

                    <div class="row">
                        <div class="col-md-3">
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

                        <div class="col-md-3">
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

                        <div class="col-md-3">
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

                        <div class="col-md-3">
                            <dl>

                                @foreach($character->skills as $skill)
                                    <dt>{{$skill->name}}:</dt>
                                    <dd>
                                        <div class="progress skill-training mb-2">
                                            <div class="progress-bar skill-bar" role="progressbar" aria-valuenow="{{$skill->xp}}" aria-valuemin="0" style="width: {{$skill->xp}}%;">{{$skill->level}}</div>
                                        </div>
                                    </dd>
                                @endforeach
                            </dl>
                        </div>
                    </div>

                    <hr />
                    <p><strong>Total Gold:</strong> {{$character->gold}}</p>
                    <p><strong>Max Inventory Space:</strong> {{$character->inventory_max}}</p>
                    <p><strong>Current Inventory Space Used:</strong> {{$character->inventory->slots->count()}}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
