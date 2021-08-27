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
                <dd>
                    @auth
                        @if (auth()->user()->hasRole('Admin'))
                            <a href="{{route('npcs.show', [
                                    'npc' => $quest->npc_id
                                ])}}">{{$quest->npc->real_name}}</a>
                        @else
                            <a href="{{route('game.npcs.show', [
                                    'npc' => $quest->npc_id
                                ])}}">{{$quest->npc->real_name}}</a>
                        @endif
                    @else
                        <a href="{{route('info.page.item', [
                                        'item' => $quest->item->id
                                    ])}}">{{$quest->item->name}}</a>
                    @endauth

                </dd>
                @if ($quest->npc->must_be_at_same_location)
                    <dt>Npc X/Y:</dt>
                    <dd>{{$quest->npc->x_position}}/{{$quest->npc->y_position}} <strong>{{$quest->npc->gameMap->name}}</strong> (You must be here to interact with the npc)</dd>
                @endif
                <dt>Required Item:</dt>
                <dd>
                    @auth
                        @if (auth()->user()->hasRole('Admin'))
                            <a href="{{route('items.item', [
                                        'item' => $quest->item->id
                                    ])}}">{{$quest->item->name}}</a>
                        @else
                            <a href="{{route('game.items.item', [
                                        'item' => $quest->item->id
                                    ])}}">{{$quest->item->name}}</a>
                        @endif
                    @else
                        <a href="{{route('info.page.item', [
                                        'item' => $quest->item->id
                                    ])}}">{{$quest->item->name}}</a>
                    @endauth

                </dd>

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
                    <dd>
                        @auth
                            @if (auth()->user()->hasRole('Admin'))
                                <a href="{{route('items.item', [
                                            'item' => $quest->reward_item
                                        ])}}">
                                    {{$quest->rewardItem->name}}
                                </a>
                            @else
                                <a href="{{route('game.items.item', [
                                    'item' => $quest->reward_item
                                    ])}}">
                                    {{$quest->rewardItem->name}}
                                </a>
                            @endif
                        @else
                            <a href="{{route('info.page.item', [
                                    'item' => $quest->reward_item
                                    ])}}">
                                {{$quest->rewardItem->name}}
                            </a>
                        @endauth
                    </dd>
                @endif

                @if (!is_null($quest->reward_gold))
                    <dt>Reward Gold:</dt>
                    <dd>{{number_format($quest->reward_gold)}}</dd>
                @endif

                @if (!is_null($quest->reward_gold_dust))
                    <dt>Reward Gold Dust:</dt>
                    <dd>{{number_format($quest->reward_gold_dust)}}</dd>
                @endif

                @if (!is_null($quest->reward_shards))
                    <dt>Reward Shards:</dt>
                    <dd>{{number_format($quest->reward_shards)}}</dd>
                @endif

                @if (!is_null($quest->reward_xp))
                    <dt>Reward XP:</dt>
                    <dd>{{number_format($quest->reward_xp)}}</dd>
                @endif

                @if ($quest->unlocks_skill)
                    <dt>Unlocks Skill:</dt>
                    <dd>
                        @auth
                            @if (auth()->user()->hasRole('Admin'))
                                <a href="{{route('skills.skill', [
                                            'skill' => $lockedSkill->id,
                                        ])}}">{{$lockedSkill->name}}</a>
                            @else
                                <a href="{{route('skill.character.info', [
                                        'skill' => $lockedSkill->id,
                                    ])}}">{{$lockedSkill->name}}</a>
                            @endif
                        @else
                            <a href="{{route('info.page.skill', [
                                        'skill' => $lockedSkill->id,
                                    ])}}">{{$lockedSkill->name}}</a>
                        @endauth
                    </dd>
                @endif
            </dl>
        </div>
    </div>
</x-cards.card-with-title>
