@extends('layouts.app')

@section('content')
<div class="container">
    <div class="container justify-content-center">
        <div class="row page-titles">
            <div class="col-md-6 align-self-right">
                <h4 class="mt-2">{{$skill->name}}</h4>
            </div>
            <div class="col-md-6 align-self-right">
                <a href="{{url()->previous()}}" class="btn btn-primary float-right ml-2">Back</a>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <p>{{$skill->description}}</p>
                        <hr />
                        @if (!$skill->can_train)
                            <p>
                                This skill cannot be trained by fighting alone. Instead, 
                                by crafting weapons of this type youll gain some xp towards its level. 
                                Certain quest items can help increase
                                the amount of xp you get from training this skill.
                            </p>
                        @endif
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
</div>
@endsection
