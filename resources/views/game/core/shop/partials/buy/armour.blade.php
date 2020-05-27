<div class="card">
    <div class="card-body">
        <h4 class="card-title">Armour</h4>

        <table class="table table-bordered text-center">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Cost</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody class="text-center">
                @foreach($armour as $armour)
                    <tr>
                        <td><a href="{{route('items.item', ['item' => $armour->id])}}">{{$armour->name}}</a></td>
                        <td>{{$armour->type}}</td>
                        <td>{{$armour->cost}}</td>
                        <td>
                            <a class="btn btn-primary" href="{{route('game.shop.buy.item')}}"
                               onclick="event.preventDefault();
                                             document.getElementById('shop-buy-form-armour-{{$armour->id}}').submit();">
                                {{ __('Buy') }}
                            </a>

                            <form id="shop-buy-form-armour-{{$armour->id}}" action="{{route('game.shop.buy.item')}}" method="POST" style="display: none;">
                                @csrf

                                <input type="hidden" name="item_id" value={{$armour->id}} />
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>