<div class="row justify-content-center">
    <div class="col-md-12">
        <div class="row">
            <div class="col-md-6">
                <h4>Item Details</h4>
                <div class="card">
                    <div class="card-body">
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
            <div class="col-md-6">
                <h4>Base Equip Stats</h4>
                <div class="card">
                    <div class="card-body">
                        <p>Values include any attached affixes</p>
                        @include('game.core.partials.equip.details.item-stat-details', ['item' => $item])
                    </div>
                </div>
            </div>
        </div>
        @if ($item->type == 'quest')
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
            <div class="alert alert-info">
                <p>
                    Quest items, like this one are used automatically. For example if the quest item gives bonuses to a crafting skill or enchanting, then the skill bonus and xp
                    will be applied upon crafting or enchanting. If its an item, like Flask of Fresh Air for example - then it gets used when you attempt to walk on water for the first time.
                </p>
            </div>
        @endif
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
