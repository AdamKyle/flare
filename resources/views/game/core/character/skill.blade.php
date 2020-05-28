@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">{{$skill->name}}</h4>
                    <dl>
                        <dt>Level:</dt>
                        <dd>{{$skill->level}}/{{$skill->max_level}}</dd>
                        <dt>Current XP:</dt>
                        <dd>{{$skill->xp}}</dd>
                        <dt>Base damage Mod:</dt>
                        <dd>{{$skill->base_damage_mod * 100 * 100}}%</dd>
                        <dt>Base ac Mod:</dt>
                        <dd>{{$skill->base_ac_mod * 100}}%</dd>
                        <dt>Base healing Mod:</dt>
                        <dd>{{$skill->base_healing_mod * 100}}%</dd>
                        <dt>Fight time out Mod:</dt>
                        <dd>{{$skill->fight_time_out_mod * 100}}%</dd>
                        <dt>Move time out Mod:</dt>
                        <dd>{{$skill->move_time_out_mod * 100}}%</dd>
                        <dt>Skill Bonus</dt>
                        <dd>{{$skill->skill_bonus * 100}}%</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
