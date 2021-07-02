<div class="row justify-content-center">
    <div class="col-md-12">
        <div class="row">
            <div class="col-md-6">
                <h4>Item Details</h4>
                <div class="card">
                    <div class="card-body">
                        <p>{!! nl2br(e($item->description)) !!}</p>
                        <hr />
                        @include('game.items.partials.item-details', ['item' => $item])
                        @guest
                        @else
                            @if (auth()->user()->hasRole('Admin'))
                                <a href="{{route('items.edit', [
                            'item' => $item
                        ])}}" class="btn btn-primary mt-3">Edit Item</a>
                            @endif
                        @endguest
                    </div>
                </div>
            </div>

            @if ($item->type == 'quest')
                <div class="col-md-6">
                    <x-cards.card-with-title title="Quest Item Details">
                        @if (!is_null($item->effect))
                            @php
                                $effects = 'N/A';

                                $effect = ItemEffects::effects($item->effect);

                                if ($effect->walkOnWater()) {
                                    $effects = 'Lets you walk on water';
                                }

                                if ($effect->labyrinth()) {
                                    $effects = 'Lets you use Traverse (beside movement actions) to traverse to Labyrinth plane';
                                }
                            @endphp
                            <p>This item lets you: {{$effect}}</p>
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
                                    <a href="{{route('game.monsters.monster', [
                                        'monster' => $monster->id
                                    ])}}">{{$monster->name}}</a>
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
                                    <a href="{{route('adventures.adventure', [
                                        'adventure' => $adventure->id
                                    ])}}">{{$adventure->name}}</a>
                                </dd>
                                <dt>Location of adventure: </dt>
                                <dd>
                                    <a href="{{route('locations.location', [
                                        'location' => $adventure->location->id
                                    ])}}">{{$adventure->location->name}}</a>
                                </dd>
                                <dt>X/Y: </dt>
                                <dd>
                                    {{$adventure->location->x}} / {{$adventure->loation->y}}
                                </dd>
                            </dl>
                        @endif
                    </x-cards.card-with-title>
                </div>
            @else
                <div class="col-md-6">
                    <h4>Base Equip Stats</h4>
                    <div class="card">
                        <div class="card-body">
                            <p>Values include any attached affixes</p>
                            @include('game.core.partials.equip.details.item-stat-details', ['item' => $item])
                        </div>
                    </div>
                </div>
            @endif

        </div>

        @if (!is_null($item->itemPrefix) || !is_null($item->itemSuffix))
            <hr />
            <div class="row">
                <div class="col-md-12">
                    <h4>Item Affixes</h4>
                    @include('game.items.partials.item-affixes', ['item' => $item])
                </div>
            </div>
        @endif
    </div>
</div>
