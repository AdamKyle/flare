@extends('layouts.app')

@section('content')
<div class="container">
    <div class="container justify-content-center">
        <div class="row page-titles">
            <div class="col-md-6 align-self-right">
                <h4 class="mt-2">{{$gameSkill->name}}</h4>
            </div>
            <div class="col-md-6 align-self-right">
                <a href="{{url()->previous()}}" class="btn btn-primary float-right ml-2">Back</a>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <p>{{$gameSkill->description}}</p>
                        <hr />
                        @if (!$gameSkill->can_train)
                            <p>
                                This skill cannot be trained by fighting alone. Instead, 
                                by crafting weapons of this type youll gain some xp towards its level. 
                                Certain quest items can help increase
                                the amount of xp you get from training this skill.
                            </p>
                        @endif
                        <dl>
                            <dt>Max Level:</dt>
                            <dd>{{$gameSkill->max_level}}</dd>
                            <dt>Base Damage Mod:</dt>
                            <dd>{{$gameSkill->base_damage_mod * 100}}%</dd>
                            <dt>Base Ac Mod:</dt>
                            <dd>{{$gameSkill->base_ac_mod * 100}}%</dd>
                            <dt>Base Healing Mod:</dt>
                            <dd>{{$gameSkill->base_healing_mod * 100}}%</dd>
                            <dt>Fight Timeout Mod:</dt>
                            <dd>{{$gameSkill->fight_time_out_mod * 100}}%</dd>
                            <dt>Move Timeout Mod:</dt>
                            <dd>{{$gameSkill->move_time_out_mod * 100}}%</dd>
                            <dt>Skill Bonus</dt>
                            <dd>{{$gameSkill->skill_bonus * 100}}%</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
