<div class="row justify-content-center">
    <div class="col-md-12">
        <div class="row">
            <div class="col-md-6">
                <x-core.cards.card-with-title title="Item Details">
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
                </x-core.cards.card-with-title>
            </div>

            @if ($item->type == 'quest')
                @include('game.items.partials.item-quest-section', [
                    'item'      => $item,
                    'monster'   => $monster,
                    'quest'     => $quest,
                    'location'  => $location,
                    'adventure' => $adventure,
                    'effects'   => $effects,
                ])
            @elseif ($item->usable)
                <div class="col-md-6">
                    @include('game.items.partials.item-usable-section', [
                        'item'   => $item,
                        'skills' => $skills,
                        'skill'  => $skill,
                    ])
                </div>
            @elseif($item->can_use_on_other_items)
                <div class="col-md-6">
                    @include('game.items.partials.item-use-on-other-items', [
                        'item'   => $item,
                    ])
                </div>
            @else
                <div class="col-md-6">
                    <x-core.cards.card-with-title title="Base Equip Stats">
                        <p>Values include any attached affixes</p>
                        @include('game.core.partials.equip.details.item-stat-details', ['item' => $item])
                    </x-core.cards.card-with-title>
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
