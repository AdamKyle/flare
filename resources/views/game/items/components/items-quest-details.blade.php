@if($item->type === 'quest')
    <div>
        <x-core.cards.card-with-title
            title="Quest Details"
            buttons="false"
        >
            @if (!is_null($item->effect))
                <p class="mt-b mb-4 font-bold text-sky-600 dark:text-sky-400">This item lets you: {{$effects}}</p>
            @endif

            <x-core.alerts.info-alert title="Info">
                Quest items, like this one are used automatically. For example if the quest item gives bonuses to a crafting skill or enchanting, then the skill bonus and xp
                will be applied upon crafting or enchanting. If it's an item, like Flask of Fresh Air for example - then it gets used when you attempt to walk on water (on surface and labyrinth) for the first time.
            </x-core.alerts.info-alert>

            @if (!is_null($monster))
                <dl class="mb-4">
                    <dt>Drops from: </dt>
                    <dd>
                        @guest
                            <a href="{{route('info.page.monster', [
                                                'monster' => $monster->id
                                            ])}}" target="_blank"><i class="fas fa-external-link-alt"></i> {{$monster->name}}</a>
                        @else
                            <a href="{{route('info.page.monster', [
                                                'monster' => $monster->id
                                            ])}}" target="_blank"><i class="fas fa-external-link-alt"></i> {{$monster->name}}</a>
                        @endif
                    </dd>
                    <dt>Drop chance: </dt>
                    <dd>
                        {{$monster->quest_item_drop_chance * 100}}%
                    </dd>
                </dl>
            @endif

            @if (!is_null($location))
                <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                <dl class="mb-4">
                    <dt>Found By Visiting: </dt>
                    <dd>
                        @auth
                            @if (auth()->user()->hasRole('Admin'))
                                <a href="{{route('locations.location', [
                                                'location' => $location->id
                                            ])}}" target="_blank"><i class="fas fa-external-link-alt"></i> {{$location->name}}</a>
                            @else
                                <a href="{{route('info.page.location', [
                                                        'location' => $location->id
                                                    ])}}" target="_blank"><i class="fas fa-external-link-alt"></i> {{$location->name}}</a>
                            @endif
                        @else
                            <a href="{{route('info.page.location', [
                                                        'location' => $location->id
                                                    ])}}" target="_blank"><i class="fas fa-external-link-alt"></i> {{$location->name}}</a>
                        @endauth

                    </dd>
                    <dt>X/Y: </dt>
                    <dd>
                        {{$location->x}} / {{$location->y}}
                    </dd>
                </dl>
            @endif
            @if (!is_null($quest))
                <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                <p class="mb-4">Players must complete the quest to obtain the item.</p>
                <dl>
                    <dt>Quest Name: </dt>
                    <dd>
                        @guest
                            <a href="{{route('info.page.quest', [
                                                            'quest' => $quest->id
                                                        ])}}" target="_blank"><i class="fas fa-external-link-alt"></i> {{$quest->name}}</a>
                        @else
                            @if (auth()->user()->hasRole('Admin'))
                                <a href="{{route('quests.show', [
                                                            'quest' => $quest->id
                                                        ])}}" target="_blank"><i class="fas fa-external-link-alt"></i> {{$quest->name}}</a>
                            @else
                                <a href="{{route('info.page.quest', [
                                                            'quest' => $quest->id
                                                        ])}}" target="_blank"><i class="fas fa-external-link-alt"></i> {{$quest->name}}</a>
                            @endif
                        @endguest
                    </dd>
                </dl>
            @endif
            @if (!is_null($item->dropLocation))
                <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                <p class="mb-4">Players cannot be auto battling for this item to drop. Looting in this location is capped at 45%. All drop chances are 1/1,000,000. Players may also eed to do relevant quests to access this location.</p>

                <dl>
                    <dt>Drops only from<sup>*</sup>: </dt>
                    <dd>{{$item->dropLocation->name}}</dd>
                    <dt>At (X/Y):</dt>
                    <dd>{{$item->dropLocation->x}}/{{$item->dropLocation->y}}</dd>
                    <dt>Located on plane:</dt>
                    <dd>{{$item->dropLocation->name}}</dd>
                </dl>
            @endif
            @if (!is_null($item->xp_bonus))
                <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                <x-core.alerts.info-alert title="Info">
                    <p class="mb-4">
                        These quest items help players to gain levels faster. However, there are two aspects to them:
                    </p>
                    <ul class="mb-4 list-disc ml-[20px]">
                        <li>
                            How much of a boost % wise.
                        </li>
                        <li>
                            Does it ignore caps?
                        </li>
                    </ul>
                    <p class="mb-4">
                        As most players know, there are three types of caps in this game when it comes to leveling: Soft (you get 50% of the remaining XP), Medium (You get 25% of the remaining XP)
                        and Hard (You get 10% of the remaining XP) Cap.
                        Soft cap starts at 1/2 the way to your max level, medium is 75% of the way and Hard is the last ten levels. If a quest item states
                        it ignores caps, you will get all the XP + the bonus AFTER any relevant skill training deductions - regardless of level.
                    </p>
                    <p class="mb-4">
                        If it does not say it ignores caps, You will NOT get the bonus once you hit soft cap which is 500 for those without the Sash of the Heavens, or half the current max level cap.
                        At which point you will no longer get the XP bonus.
                    </p>
                </x-core.alerts.info-alert>
                <dl>
                    <dt>XP Bonus:</dt>
                    <dd>{{$item->xp_bonus * 100}}%</dd>
                    <dt>Ignores Caps:</dt>
                    <dd>{{$item->ignores_caps ? 'Yes' : 'No'}}</dd>
                </dl>
            @endif
        </x-core.cards.card-with-title>
    </div>
@endif
