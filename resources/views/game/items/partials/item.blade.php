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
        <hr />
        <div class="row">
            <div class="col-md-12">
                <h4>Item Affixes</h4>
                @include('game.items.partials.item-affixes', ['item' => $item])
            </div>
        </div>
    </div>
</div>
