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
                        <input wire:model="search" class="form-control" type="text" placeholder="Search items...">
                    </div>
                </div>
                <table class="table table-bordered data-table">
                    <thead>
                        <tr>
                            <th>
                                <a wire:click.prevent="sortBy('name')" role="button" href="#">
                                    Name
                                    @include('admin.partials.data-table-icons', [
                                        'field' => 'name'
                                    ])
                                </a>
                            </th>
                            <th>
                                <a wire:click.prevent="sortBy('type')" role="button" href="#">
                                    Type
                                    @include('admin.partials.data-table-icons', [
                                        'field' => 'type'
                                    ])
                                </a>
                            </th>
                            <th>
                                <a wire:click.prevent="sortBy('base_damage')" role="button" href="#">
                                    Base Damage
                                    @include('admin.partials.data-table-icons', [
                                        'field' => 'base_damage'
                                    ])
                                </a>
                            </th>
                            <th>
                                <a wire:click.prevent="sortBy('base_ac')" role="button" href="#">
                                    Base AC
                                    @include('admin.partials.data-table-icons', [
                                        'field' => 'base_ac'
                                    ])
                                </a>
                            </th>
                            <th>
                                <a wire:click.prevent="sortBy('base_healing')" role="button" href="#">
                                    Base Healing
                                    @include('admin.partials.data-table-icons', [
                                        'field' => 'base_healing'
                                    ])
                                </a>
                            </th>
                            <th>
                                <a wire:click.prevent="sortBy('cost')" role="button" href="#">
                                    Cost
                                    @include('admin.partials.data-table-icons', [
                                        'field' => 'cost'
                                    ])
                                </a>
                            </th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                            <tr>
                                <td><a href="{{route('items.item', [
                                    'item' => $item->id
                                ])}}"><x-item-display-color :item="$item" /></a></td>
                                <td>{{$item->type}}</td>
                                <td>{{is_null($item->base_damage) ? 'N/A' : $item->base_damage}}</td>
                                <td>{{is_null($item->base_ac) ? 'N/A' : $item->base_ac}}</td>
                                <td>{{is_null($item->base_healing) ? 'N/A' : $item->base_healing}}</td>
                                <td>{{is_null($item->cost) ? 'N/A' : $item->cost}}</td>
                                <td>
                                    @if(auth()->user()->hasRole('Admin'))
                                        <a class="btn btn-danger" href="{{route('items.delete', [
                                            'item' => $item->id
                                        ])}}"
                                                        onclick="event.preventDefault();
                                                        document.getElementById('delete-item-{{$item->id}}').submit();">
                                            {{ __('Delete') }}
                                        </a>

                                        <form id="delete-item-{{$item->id}}" action="{{route('items.delete', [
                                            'item' => $item->id
                                        ])}}" method="DELETE" style="display: none;">
                                            @csrf
                                        </form>
                                    @else
                                        <a class="btn btn-primary" href="{{route('game.shop.buy.item')}}"
                                                        onclick="event.preventDefault();
                                                        document.getElementById('shop-buy-form-item-{{$item->id}}').submit();">
                                            {{ __('Buy') }}
                                        </a>

                                        <form id="shop-buy-form-item-{{$item->id}}" action="{{route('game.shop.buy.item')}}" method="POST" style="display: none;">
                                            @csrf

                                            <input type="hidden" name="item_id" value={{$item->id}} />
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="row">
                    <div class="col">
                        {{ $items->links() }}
                    </div>
            
                    <div class="col text-right text-muted">
                        Showing {{ $items->firstItem() }} to {{ $items->lastItem() }} out of {{ $items->total() }} results
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
