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
                    <p>{!! nl2br(e($skill->description)) !!}</p>
                    <hr />
                    @if (!$skill->can_train)
                        @if (stristr($skill->name, 'Crafting') !== false)
                            <div class="alert alert-info">
                                This skill can only be trained by crafting.
                            </div>
                        @elseif ($skill->type()->isEnchanting())
                            <div class="alert alert-info">
                                This skill can only be trained by enchanting.
                            </div>
                        @elseif($skill->type()->isAlchemy())
                            <div class="alert alert-info">
                                This skill can only be trained by using alchemy, which is found in the same menu as Crafting/Enchanting..
                            </div>
                        @endif
                    @endif
                    <div class="alert alert-info mb-2 mt-2">
                        Skill bonus applies to skills that affects things in battle, such as accuracy or dodge or even looting.
                        <br />
                        If a skill does not have a skill bonus, check the other modifiers.
                    </div>
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
                        <dd>{{$skill->skill_bonus * 100}}% When Using</dd>
                        <dt>Skill Training Bonus</dt>
                        <dd>{{$skill->skill_training_bonus * 100}}% When Skill XP is awarded</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
    @if ($skill->is_locked)
        <div class="row justify-content-center">
            <div class="col-md-12">
                <x-cards.card-with-title title="Locked">
                    <p>This skill is locked and cannot be trained until you complete a quest.</p>
                    <dl>
                        <dt>
                            Quest Name:
                        </dt>
                        <dd>
                            <a href="{{route('game.quests.show', [
                                'quest' => $quest->id
                            ])}}">{{$quest->name}}</a>
                        </dd>
                    </dl>
                    <p class="mt-3">Upon completing the quest, the skill will be unlocked, there will be a new action called: Alchemy under Crafting/Enchanting
                    to allow you to craft new items.</p>
                </x-cards.card-with-title>
            </div>
        </div>
    @endif
@endsection
