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
                <p class="my-2">
                    If a location has a quest reward associated with it, all you have to do is physically
                    visit the location to get the quest reward.
                </p>

                <p>
                    Quest items, like this one are used automatically. For example if the quest item gives bonuses to a crafting skill or enchanting, then the skill bonus and xp
                    will be applied upon crafting or enchanting. If it's an item, like Flask of Fresh Air for example - then it gets used when you attempt to walk on water (on surface and labyrinth) for the first time.
                </p>
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
        </x-core.cards.card-with-title>
    </div>
@endif
