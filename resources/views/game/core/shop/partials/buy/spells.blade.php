<div class="card">
    <div class="card-body">
        <h4 class="card-title">Spells</h4>

        <table class="table table-bordered text-center">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Base Damage</th>
                    <th>Cost</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody class="text-center">
                @foreach($spells as $spell)
                    <tr>
                        <td>{{$spell->name}}</td>
                        <td>{{!is_null($spell->base_damage) ? $spell->base_damage : 'N/A'}}</td>
                        <td>{{$spell->cost}}</td>
                        <td>
                            <a class="btn btn-primary" href="{{route('game.shop.buy.item')}}"
                               onclick="event.preventDefault();
                                             document.getElementById('shop-buy-form-spell-{{$spell->id}}').submit();">
                                {{ __('Buy') }}
                            </a>

                            <form id="shop-buy-form-spell-{{$spell->id}}" action="{{route('game.shop.buy.item')}}" method="POST" style="display: none;">
                                @csrf

                                <input type="hidden" name="item_id" value={{$spell->id}} />
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>