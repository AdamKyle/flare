<div class="grid lg:grid-cols-2 gap-4">
    <div>
        <p class="mt-3 mb-3">This quest belongs to an NPC, whom you must be on the same place as to complete.</p>
        <p class="mb-3">To complete all a quest all you have to do is on the same plane, then click Quests tab, click
            the quest, click complete.</p>
        <p class="mb-3">You must have the required items, currencies and/or faction points needed.</p>

        @if (!is_null($quest->only_for_event))
            @if ($quest->eventType()->isWinterEvent())
                <x-core.alerts.info-alert>
                    <p>This quest chain is only available during the Winter Event</p>
                </x-core.alerts.info-alert>
            @endif
        @endif

        <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
        <dl>
            <dt>Quest Name:</dt>
            <dd>{{ $quest->name }}</dd>
            <dt>Npc Name:</dt>
            <dd>
                @auth
                    @if (auth()->user()->hasRole('Admin'))
                        <a
                            href="{{ route('npcs.show', [
                                'npc' => $quest->npc_id,
                            ]) }}">{{ $quest->npc->real_name }}</a>
                    @else
                        <a
                            href="{{ route('game.npcs.show', [
                                'npc' => $quest->npc_id,
                            ]) }}">{{ $quest->npc->real_name }}</a>
                    @endif
                @else
                    <a
                        href="{{ route('info.page.npc', [
                            'npc' => $quest->npc_id,
                        ]) }}">{{ $quest->npc->real_name }}</a>
                @endauth
            </dd>
            @if ($quest->npc->must_be_at_same_location)
                <dt>Npc X/Y:</dt>
                <dd>{{ $quest->npc->x_position }}/{{ $quest->npc->y_position }}
                    <strong>{{ $quest->npc->gameMap->name }}</strong> (You will be moved to this location upon handing
                    in the quest.)</dd>
            @endif
            <dt>Required Item:</dt>
            <dd>
                @if (!is_null($quest->item))
                    @auth
                        @if (auth()->user()->hasRole('Admin'))
                            <a
                                href="{{ route('items.item', [
                                    'item' => $quest->item->id,
                                ]) }}">{{ $quest->item->name }}</a>
                        @else
                            <a
                                href="{{ route('game.items.item', [
                                    'item' => $quest->item->id,
                                ]) }}">{{ $quest->item->name }}</a>
                        @endif
                    @else
                        <a
                            href="{{ route('info.page.item', [
                                'item' => $quest->item->id,
                            ]) }}">{{ $quest->item->name }}</a>
                    @endauth
                @else
                    None Required.
                @endif

            </dd>
            <dt>Required Secondary Item:</dt>
            <dd>
                @if (!is_null($quest->secondary_required_item))
                    @auth
                        @if (auth()->user()->hasRole('Admin'))
                            <a
                                href="{{ route('items.item', [
                                    'item' => $quest->secondaryItem->id,
                                ]) }}">{{ $quest->secondaryItem->name }}</a>
                        @else
                            <a
                                href="{{ route('game.items.item', [
                                    'item' => $quest->secondaryItem->id,
                                ]) }}">{{ $quest->secondaryItem->name }}</a>
                        @endif
                    @else
                        <a
                            href="{{ route('info.page.item', [
                                'item' => $quest->secondaryItem->id,
                            ]) }}">{{ $quest->secondaryItem->name }}</a>
                    @endauth
                @else
                    None Required.
                @endif

            </dd>

            @if (!is_null($quest->gold_cost))
                <dt>Required Gold:</dt>
                <dd>{{ $quest->gold_cost }}</dd>
            @endif

            @if (!is_null($quest->gold_dust_cost))
                <dt>Required Gold Dust Cost:</dt>
                <dd>{{ $quest->gold_dust_cost }}</dd>
            @endif

            @if (!is_null($quest->shards_cost))
                <dt>Required Shards Cost:</dt>
                <dd>{{ $quest->shards_cost }}</dd>
            @endif

            @if (!is_null($quest->required_quest_id))
                <dt>Must Complete Quest:</dt>
                <dd>{{ $quest->requiredQuest->name }}</dd>
            @endif
        </dl>
    </div>
    <div>
        <p class="mt-3 mb-3">Upon completing the quest you will receive:</p>

        <dl>
            @if (!is_null($quest->reward_item))
                <dt>Reward Item:</dt>
                <dd>
                    @auth
                        @if (auth()->user()->hasRole('Admin'))
                            <a
                                href="{{ route('items.item', [
                                    'item' => $quest->reward_item,
                                ]) }}">
                                {{ $quest->rewardItem->name }}
                            </a>
                        @else
                            <a
                                href="{{ route('game.items.item', [
                                    'item' => $quest->reward_item,
                                ]) }}">
                                {{ $quest->rewardItem->name }}
                            </a>
                        @endif
                    @else
                        <a
                            href="{{ route('info.page.item', [
                                'item' => $quest->reward_item,
                            ]) }}">
                            {{ $quest->rewardItem->name }}
                        </a>
                    @endauth
                </dd>
            @endif

            @if (!is_null($quest->reward_gold))
                <dt>Reward Gold:</dt>
                <dd>{{ number_format($quest->reward_gold) }}</dd>
            @endif

            @if (!is_null($quest->reward_gold_dust))
                <dt>Reward Gold Dust:</dt>
                <dd>{{ number_format($quest->reward_gold_dust) }}</dd>
            @endif

            @if (!is_null($quest->reward_shards))
                <dt>Reward Shards:</dt>
                <dd>{{ number_format($quest->reward_shards) }}</dd>
            @endif

            @if (!is_null($quest->reward_xp))
                <dt>Reward XP:</dt>
                <dd>{{ number_format($quest->reward_xp) }}</dd>
            @endif

            @if ($quest->unlocks_skill)
                <dt>Unlocks Skill:</dt>
                <dd>
                    @auth
                        @if (auth()->user()->hasRole('Admin'))
                            <a
                                href="{{ route('skills.skill', [
                                    'skill' => $lockedSkill->id,
                                ]) }}">{{ $lockedSkill->name }}</a>
                        @else
                            <a
                                href="{{ route('skill.character.info', [
                                    'skill' => $lockedSkill->id,
                                ]) }}">{{ $lockedSkill->name }}</a>
                        @endif
                    @else
                        <a
                            href="{{ route('info.page.skill', [
                                'skill' => $lockedSkill->id,
                            ]) }}">{{ $lockedSkill->name }}</a>
                    @endauth
                </dd>
            @endif
        </dl>
    </div>
</div>
