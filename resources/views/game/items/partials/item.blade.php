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

                @if ($item->appliedHolyStacks->isNotEmpty())
                    <x-core.cards.card-with-title title="Applied Holy Stacks" css="tw-mt-4">
                        @include('game.items.partials.item-holy-section', ['item' => $item])
                    </x-core.cards.card-with-title>
                @endif
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
            @elseif ($item->type === 'trinket')
                <div class="col-md-6">
                    <x-core.cards.card-with-title title="Trinket Details" css="tw-mt-4">
                        <x-core.alerts.info-alert>
                            <p>
                                Trinkets cannot have Holy Stacks Applied and cannot they have Enchantments applied.
                                Trinkets can be sold on the market for 100X their Gold Dust cost in Gold.
                            </p>
                        </x-core.alerts.info-alert>
                        <dl>
                            <dt>Ambush Chance %</dt>
                            <dd>{{$item->ambush_chance * 100}}%</dd>
                            <dt>Ambush Resist %</dt>
                            <dd>{{$item->ambush_resistance * 100}}%</dd>
                            <dt>Counter Chance %</dt>
                            <dd>{{$item->counter_chance * 100}}%</dd>
                            <dt>Counter Resist %</dt>
                            <dd>{{$item->counter_resistance * 100}}%</dd>
                        </dl>
                    </x-core.cards.card-with-title>
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
