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
                                ])}}">{{$slot->item->name}}</a></td>
                                <td>{{$slot->item->type}}</td>
                                <td>{{is_null($slot->item->base_damage) ? 'N/A' : $slot->item->base_damage}}</td>
                                <td>{{is_null($slot->item->base_ac) ? 'N/A' : $slot->item->base_ac}}</td>
                                <td>{{is_null($slot->item->base_healing) ? 'N/A' : $slot->item->base_healing}}</td>
                                <td>{{$slot->item->cost}}</td>
                                <td>
                                    <a class="btn btn-primary" href="{{route('game.shop.sell.item')}}"
                                                    onclick="event.preventDefault();
                                                    document.getElementById('shop-sell-form-slot-{{$slot->id}}').submit();">
                                        {{ __('Sell') }}
                                    </a>

                                    <form id="shop-sell-form-slot-{{$slot->id}}" action="{{route('game.shop.sell.item')}}" method="POST" style="display: none;">
                                        @csrf

                                        <input type="hidden" name="slot_id" value={{$slot->id}} />
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="row">
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
