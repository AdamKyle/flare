<div class="row justify-content-center">
    <div class="col-md-12">
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

        <h4>Base Equip Stats</h4>
        <div class="card">
            <div class="card-body">
                <p class="text-muted mb-2 mt-2" style="font-size: 12px; font-style: italic;">All values include any attached affixes and any additional modifiers and will be applied upon equiping.</p>
                @include('game.core.partials.equip.details.item-stat-details', ['item' => $item])
            </div>
        </div>

        <h4>Item Affixes</h4>
        <div class="card" style="margin-bottom: 100px;">
            <div class="card-body">
                @include('game.items.partials.item-affixes', ['item' => $item])
            </div>
        </div>
    </div>
</div>