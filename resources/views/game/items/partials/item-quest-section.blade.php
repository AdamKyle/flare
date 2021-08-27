<div class="col-md-6">
    <x-cards.card-with-title title="Quest Item Details">
        @if (!is_null($item->effect))
            <p>This item lets you: {{$effects}}</p>
        @endif
        <div class="alert alert-info mb-3 mt-2">
            <p>
                Quest items, like this one are used automatically. For example if the quest item gives bonuses to a crafting skill or enchanting, then the skill bonus and xp
                will be applied upon crafting or enchanting. If its an item, like Flask of Fresh Air for example - then it gets used when you attempt to walk on water for the first time.
            </p>
        </div>

        @if (!is_null($monster))
            <hr>
            <dl>
                <dt>Drops from: </dt>
                <dd>
                    @guest
                        <a href="{{route('info.page.monster', [
                                        'monster' => $monster->id
                                    ])}}">{{$monster->name}}</a>
                    @else
                        <a href="{{route('game.monsters.monster', [
                                        'monster' => $monster->id
                                    ])}}">{{$monster->name}}</a>
                    @endif
                </dd>
                <dt>Drop chance: </dt>
                <dd>
                    {{$monster->quest_item_drop_chance * 100}}%
                </dd>
            </dl>
        @endif
        @if (!is_null($location))
            <hr>
            <dl>
                <dt>Found By Visiting: </dt>
                <dd>
                    <a href="{{route('locations.location', [
                                        'location' => $location->id
                                    ])}}">{{$location->name}}</a>
                </dd>
                <dt>X/Y: </dt>
                <dd>
                    {{$location->x}} / {{$loation->y}}
                </dd>
            </dl>
        @endif
        @if (!is_null($adventure))
            <hr>
            <dl>
                <dt>Adventure Name: </dt>
                <dd>
                    @auth
                        @if (auth()->user()->hasRole('Admin'))
                            <a href="{{route('adventures.adventure', [
                                                'adventure' => $adventure->id
                                            ])}}">{{$adventure->name}}</a>
                        @else
                            <a href="{{route('map.adventures.adventure', [
                                                'adventure' => $adventure->id
                                            ])}}">{{$adventure->name}}</a>
                        @endif
                    @else
                        <a href="{{route('info.page.adventure', [
                                                'adventure' => $adventure->id
                                            ])}}">{{$adventure->name}}</a>
                    @endauth
                </dd>
                <dt>Location of adventure: </dt>
                <dd>
                    @auth
                        @if (auth()->user()->hasRole('Admin'))
                            <a href="{{route('locations.location', [
                                                'location' => $adventure->location->id
                                            ])}}">{{$adventure->location->name}}</a>
                        @else
                            <a href="{{route('game.locations.location', [
                                                'location' => $adventure->location->id
                                            ])}}">{{$adventure->location->name}}</a>
                        @endif
                    @else
                        <a href="{{route('info.page.location', [
                                                'location' => $adventure->location->id
                                            ])}}">{{$adventure->location->name}}</a>
                    @endauth

                </dd>
                <dt>X/Y: </dt>
                <dd>
                    {{$adventure->location->x}} / {{$adventure->loation->y}}
                </dd>
            </dl>
        @endif
        @if (!is_null($quest))
            <hr>
            <dl>
                <dt>Quest Name: </dt>
                <dd>
                    @auth
                        @if (auth()->user()->hasRole('Admin'))
                            <a href="{{route('quests.show', [
                                                    'quest' => $quest->id
                                                ])}}">{{$quest->name}}</a>
                        @else
                            <a href="{{route('game.quests.show', [
                                                    'quest' => $quest->id
                                                ])}}">{{$quest->name}}</a>
                        @endif
                    @else
                        <a href="{{route('information.quests.quest', [
                                                    'quest' => $quest->id
                                                ])}}">{{$quest->name}}</a>
                    @endauth
                </dd>
            </dl>
        @endif
    </x-cards.card-with-title>
</div>
