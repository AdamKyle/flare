@extends('layouts.app')

@section('content')
<div class={{isset($customClass) ? $customClass . ' container' : 'container'}}>
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
                            <dt>Max Level:</dt>
                            <dd>{{$skill->max_level}}</dd>
                            <dt>Base Damage Mod:</dt>
                            <dd>{{$skill->base_damage_mod_bonus_per_level * 100}}%</dd>
                            <dt>Base Ac Mod:</dt>
                            <dd>{{$skill->base_ac_mod_bonus_per_level * 100}}%</dd>
                            <dt>Base Healing Mod:</dt>
                            <dd>{{$skill->base_healing_mod_bonus_per_level * 100}}%</dd>
                            <dt>Fight Timeout Mod:</dt>
                            <dd>{{$skill->fight_time_out_mod_bonus_per_level * 100}}%</dd>
                            <dt>Move Timeout Mod:</dt>
                            <dd>{{$skill->move_time_out_mod_bonus_per_level * 100}}%</dd>
                            <dt>Skill Bonus</dt>
                            <dd>{{$skill->skill_bonus_per_level * 100}}%</dd>
                        </dl>
                        @guest
                        @else
                            @if (auth()->user()->hasRole('Admin'))
                                <a href="{{route('skill.edit', [
                                    'skill' => $skill
                                ])}}" class="btn btn-primary mt-2">Edit</a>
                            @endif
                        @endguest
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
