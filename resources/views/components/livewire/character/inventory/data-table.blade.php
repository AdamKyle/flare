<div class="row justify-content-center">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col form-inline">
                        Per Page: &nbsp;
                        <select wire:model="perPage" class="form-control">
                            <option>10</option>
                            <option>15</option>
                            <option>25</option>
                        </select>
                    </div>
            
                    <div class="col">
                        <input wire:model="search" class="form-control" type="text" placeholder="Search inventory...">
                    </div>
                </div>
                <table class="table table-bordered data-table">
                    <thead>
                        <tr>
                            <th>
                                <a wire:click.prevent="sortBy('items.name')" role="button" href="#">
                                    Name
                                    @include('admin.partials.data-table-icons', [
                                        'field' => 'items.name'
                                    ])
                                </a>
                            </th>
                            <th>
                                <a wire:click.prevent="sortBy('items.type')" role="button" href="#">
                                    Type
                                    @include('admin.partials.data-table-icons', [
                                        'field' => 'items.type'
                                    ])
                                </a>
                            </th>
                            <th>
                                <a wire:click.prevent="sortBy('items.base_damage')" role="button" href="#">
                                    Base Damage
                                    @include('admin.partials.data-table-icons', [
                                        'field' => 'items.base_damage'
                                    ])
                                </a>
                            </th>
                            <th>
                                <a wire:click.prevent="sortBy('items.base_ac')" role="button" href="#">
                                    Base AC
                                    @include('admin.partials.data-table-icons', [
                                        'field' => 'items.base_ac'
                                    ])
                                </a>
                            </th>
                            <th>
                                <a wire:click.prevent="sortBy('items.base_healing')" role="button" href="#">
                                    Base Healing
                                    @include('admin.partials.data-table-icons', [
                                        'field' => 'items.base_healing'
                                    ])
                                </a>
                            </th>
                            <th>
                                <a wire:click.prevent="sortBy('items.cost')" role="button" href="#">
                                    Cost
                                    @include('admin.partials.data-table-icons', [
                                        'field' => 'items.cost'
                                    ])
                                </a>
                            </th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($slots as $slot)
                            <tr>
                                <td><a href="{{route('items.item', [
                                    'item' => $slot->item->id
                                ])}}"><x-item-display-color :item="$slot->item" /></a></td>
                                <td>{{$slot->item->type}}</td>
                                <td>{{is_null($slot->item->base_damage) ? 'N/A' : $slot->item->base_damage}}</td>
                                <td>{{is_null($slot->item->base_ac) ? 'N/A' : $slot->item->base_ac}}</td>
                                <td>{{is_null($slot->item->base_healing) ? 'N/A' : $slot->item->base_healing}}</td>
                                <td>{{is_null($slot->item->Cost) ? 'N/A' : $slot->item->cost}}</td>
                                <td>
                                    @if ($allowInventoryManagement)
                                        <div class="dropdown">
                                            <button class="btn btn-primary dropdown-toggle" type="button" id="actionsButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            Actions
                                            </button>
                                            <div class="dropdown-menu" aria-labelledby="actionsButton">
                                            @if (!$slot->equipped)
                                                <form id="item-comparison-{{$slot->id}}" action="{{route('game.inventory.compare')}}" method="GET" style="display: none">
                                                    @csrf
                
                                                    <input type="hidden" name="slot_id" value={{$slot->id}} />
                
                                                    @if ($slot->item->crafting_type === 'armour')
                                                        <input type="hidden" name="item_to_equip_type" value={{$slot->item->type}} />
                                                    @endif
                                                </form>
                
                                                <a class="dropdown-item" href="{{route('game.inventory.compare')}}"
                                                    onclick="event.preventDefault();
                                                                document.getElementById('item-comparison-{{$slot->id}}').submit();">
                                                    {{ __('Equip') }}
                
                                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#slot-{{$slot->id}}">Destroy</a>
                                            @else
                                                <form id="item-unequip-{{$slot->id}}" action="{{route('game.inventory.unequip')}}" method="POST" style="display: none">
                                                    @csrf
                
                                                    <input type="hidden" name="item_to_remove" value={{$slot->id}} />
                                                </form>
                                                <a class="dropdown-item" href="{{route('game.inventory.unequip')}}"
                                                    onclick="event.preventDefault();
                                                                document.getElementById('item-unequip-{{$slot->id}}').submit();">
                                                    {{ __('Unequip') }}</a>
                                            @endif
                
                                            </div>
                                        </div>
                
                                        @include('game.core.partials.destroy-modal', ['slot' => $slot])
                                    @else
                                        <a class="btn btn-primary" href="{{route('game.shop.sell.item')}}"
                                                        onclick="event.preventDefault();
                                                        document.getElementById('shop-sell-form-slot-{{$slot->id}}').submit();">
                                            {{ __('Sell') }}
                                        </a>

                                        <form id="shop-sell-form-slot-{{$slot->id}}" action="{{route('game.shop.sell.item')}}" method="POST" style="display: none;">
                                            @csrf

                                            <input type="hidden" name="slot_id" value={{$slot->id}} />
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="row">
                    @if ($allowUnequipAll)
                        <div class="col text-align-right">
                            <a class="btn btn-danger btn-sm" href="{{ route('logout') }}"
                                onclick="event.preventDefault();
                                            document.getElementById('unequip-all').submit();"
                            >
                                Unequip All
                            </a>

                            <form id="unequip-all" action="{{ route('game.unequip.all') }}" method="POST" style="display: none;">
                                @csrf
                            </form>
                        </div>
                    @endif

                    <div class="col">
                        {{ $slots->links() }}
                    </div>
            
                    <div class="col text-right text-muted">
                        Showing {{ $slots->firstItem() }} to {{ $slots->lastItem() }} out of {{ $slots->total() }} results
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
