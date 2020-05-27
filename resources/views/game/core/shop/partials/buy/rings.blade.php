<div class="card">
    <div class="card-body">
        <h4 class="card-title">Rings</h4>

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
                @foreach($rings as $ring)
                    <tr>
                        <td>{{$ring->name}}</td>
                        <td>{{$ring->base_damage}}</td>
                        <td>{{$ring->cost}}</td>
                        <td>
                            <a class="btn btn-primary" href="{{route('game.shop.buy.item')}}"
                               onclick="event.preventDefault();
                                             document.getElementById('shop-buy-form-rings-{{$ring->id}}').submit();">
                                {{ __('Buy') }}
                            </a>

                            <form id="shop-buy-form-rings-{{$ring->id}}" action="{{route('game.shop.buy.item')}}" method="POST" style="display: none;">
                                @csrf

                                <input type="hidden" name="item_id" value={{$ring->id}} />
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>