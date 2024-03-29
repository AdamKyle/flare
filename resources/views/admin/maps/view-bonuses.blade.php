@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Map Bonuses</h4>
                        <dl>
                            <dt>XP Bonus:</dt>
                            <dd>{{!is_null($gameMap->xp_bonus) ? ($gameMap->xp_bonus * 100) . '%' : '0%'}}</dd>
                            <dt>Skill Training Bonus:</dt>
                            <dd>{{!is_null($gameMap->skill_training_bonus) ? ($gameMap->skill_training_bonus * 100) . '%' : '0%'}}</dd>
                            <dt>Drop Chance Bonus:</dt>
                            <dd>{{!is_null($gameMap->drop_chance_bonus) ? ($gameMap->drop_chance_bonus * 100) . '%' : '0%'}}</dd>
                            <dt>Enemy Stat Bonus:</dt>
                            <dd>{{!is_null($gameMap->enemy_stat_bonus) ? ($gameMap->enemy_stat_bonus * 100) . '%' : '0%'}}</dd>
                            <dt>Character Damage Deduction:</dt>
                            <dd>{{!is_null($gameMap->character_attack_reduction) ? ($gameMap->character_attack_reduction * 100) . '%' : '0%'}}</dd>
                            @if (!is_null($gameMap->required_location_id))
                                <dt>Must be at location (X/Y):</dt>
                                <dd>{{$gameMap->requiredLocation->x}}/{{$gameMap->requiredLocation->y}}</dd>
                                <dt>On Plane:</dt>
                                <dd>{{$gameMap->requiredLocation->map->name}}</dd>
                            @endif
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
