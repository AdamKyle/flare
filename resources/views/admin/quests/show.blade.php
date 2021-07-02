@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row page-titles">
            <div class="col-md-6 align-self-right">
                <h4 class="mt-2">{{$quest->name}}</h4>
            </div>
            <div class="col-md-6 align-self-right">
                <a href="{{route('home')}}" class="btn btn-success float-right ml-2">Home</a>
            </div>
        </div>
        <hr />
        <x-cards.card-with-title title="Details">
            <div class="row">
                <div class="col-md-6">
                    <p>By speaking with this npc {{$quest->npc->must_be_at_same_location ? '(while at the same location)' : ':'}}</p>
                    <code>/m {{$quest->npc->name}}: {{$quest->npc->commands->first()->command}}</code>
                    <p class="mt-2 mb-2">The NPC will take the following from you:</p>
                    <dl class="mt-3 mb-3">
                        <dt>Quest Name:</dt>
                        <dd>{{$quest->name}}</dd>
                        <dt>Npc Name:</dt>
                        <dd><a href="{{route('npcs.show', [
                            'npc' => $quest->npc_id
                        ])}}">{{$quest->npc->real_name}}</a></dd>
                        @if ($quest->npc->must_be_at_same_location)
                            <dt>Npc X/Y:</dt>
                            <dd>{{$quest->npc->x_position}}/{{$quest->npc->y_position}} (You must be here to interact with the npc)</dd>
                        @endif
                        <dt>Required Item:</dt>
                        <dd><a href="{{route('items.item', [
                            'item' => $quest->item_id
                        ])}}">{{$quest->item->name}}</a></dd>

                        @if (!is_null($quest->gold_cost))
                            <dt>Required Gold:</dt>
                            <dd>{{$quest->gold_cost}}</dd>
                        @endif

                        @if (!is_null($quest->gold_dust_cost))
                            <dt>Required Gold Dust Cost:</dt>
                            <dd>{{$quest->gold_dust_cost}}</dd>
                        @endif

                        @if (!is_null($quest->shards_cost))
                            <dt>Required Shards Cost:</dt>
                            <dd>{{$quest->shards_cost}}</dd>
                        @endif
                    </dl>
                </div>

                <div class="col-md-6">
                    <p>Upon giving the NPC what they want, you will be rewarded with the following:</p>
                    <dl class="mt-3 mb-3">


                        @if (!is_null($quest->reward_item))
                            <dt>Reward Item:</dt>
                            <dd><a href="{{route('items.item', [
                                'item' => $quest->reward_item
                            ])}}">{{$quest->rewardItem->name}}</a></dd>
                        @endif

                        @if (!is_null($quest->reward_gold))
                            <dt>Reward Gold:</dt>
                            <dd>{{$quest->reward_gold}}</dd>
                        @endif

                        @if (!is_null($quest->reward_gold_dust))
                            <dt>Reward Gold Dust:</dt>
                            <dd>{{$quest->reward_gold_dust}}</dd>
                        @endif

                        @if (!is_null($quest->reward_shards))
                            <dt>Reward Shards:</dt>
                            <dd>{{$quest->reward_shards}}</dd>
                        @endif

                        @if (!is_null($quest->reward_xp))
                            <dt>Reward XP:</dt>
                            <dd>{{$quest->reward_xp}}</dd>
                        @endif

                        @if ($quest->unlocks_skill)
                            <dt>Unlocks Skill:</dt>
                            <dd><a href="{{route('skills.skill', [
                                'skill' => $lockedSkill->id,
                            ])}}">{{$lockedSkill->name}}</a></dd>
                        @endif
                    </dl>
                </div>
            </div>
        </x-cards.card-with-title>
    </div>
@endsection
