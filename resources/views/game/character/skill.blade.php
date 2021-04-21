@extends('layouts.app')

@section('content')
    <x-core.page-title 
        title="{{$skill->name}}"
        route="{{url()->previous()}}"
        link="Back"
        color="success"
    ></x-core.page-title>

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
                        <dd>{{$skill->level}} / {{$skill->max_level}}</dd>
                        <dt>Current XP:</dt>
                        <dd>{{is_null($skill->xp) ? 0 : $skill->xp}} / {{$skill->xp_max}}</dd>
                        <dt>Base Damage Mod:</dt>
                        <dd>{{$skill->base_damage_mod * 100}}%</dd>
                        <dt>Base AC Mod:</dt>
                        <dd>{{$skill->base_ac_mod * 100}}%</dd>
                        <dt>Base Healing Mod:</dt>
                        <dd>{{$skill->base_healing_mod * 100}}%</dd>
                        <dt>Fight Timeout Mod:</dt>
                        <dd>{{$skill->fight_time_out_mod * 100}}%</dd>
                        <dt>Move Timeout Mod:</dt>
                        <dd>{{$skill->move_time_out_mod * 100}}%</dd>
                        <dt>Skill Bonus</dt>
                        <dd>{{$skill->skill_bonus * 100}}%</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
@endsection
