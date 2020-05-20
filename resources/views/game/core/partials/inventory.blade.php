<table class="table table-bordered text-center">
    <thead class="thead-dark">
        <tr>
            <th>Name</th>
            <th>Base Damage</th>
            @if ($actions === 'manage')
                <th>Equiped</th>
                <th>Position</th>
            @endif
            <th>Cost</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody class="text-center">
        @foreach($inventory as $slot)
            <tr>
                <td>{{$slot->item->name}}</td>
                <td>{{$slot->item->base_damage}}</td>

                @if ($actions === 'manage')
                    <td>{{$slot->equipped ? 'Yes' : 'No'}}</td>
                    <td>{{$slot->position}}</td>
                @endif
                <td>{{$slot->item->cost}}</td>

                @if ($actions === 'manage')
                    <td>
                        <div class="dropdown">
                          <button class="btn btn-primary dropdown-toggle" type="button" id="actionsButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            Actions
                          </button>
                          <div class="dropdown-menu" aria-labelledby="actionsButton">
                            @if (!$slot->equipped)
                                <form id="item-comparison" action="{{route('game.inventory.compare')}}" method="GET" style="display: none">
                                    @csrf

                                    <input type="hidden" name="slot_id" value={{$slot->id}} />

                                    <input type="hidden" name="item_to_equip_type" value={{$slot->item->type}} />
                                </form>

                                <a class="dropdown-item" href="{{route('game.inventory.compare')}}"
                                   onclick="event.preventDefault();
                                                 document.getElementById('item-comparison').submit();">
                                    {{ __('Equip') }}

                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#slot-{{$slot->id}}">Destroy</a>
                            @else
                                <form id="item-unequip" action="{{route('game.inventory.unequip')}}" method="POST" style="display: none">
                                    @csrf

                                    <input type="hidden" name="item_to_remove" value={{$slot->id}} />
                                </form>
                                <a class="dropdown-item" href="{{route('game.inventory.unequip')}}"
                                   onclick="event.preventDefault();
                                                 document.getElementById('item-unequip').submit();">
                                    {{ __('Unequip') }}
                            @endif

                          </div>
                        </div>

                        @include('game.core.partials.destroy-modal', ['slot' => $slot])
                    </td>
                @else
                    <td><a href="#" class="btn btn-primary">Sell</a></td>
                @endif
            </tr>
        @endforeach
    </tbody>
</table>


