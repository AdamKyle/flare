<div class="card">
    <div class="card-body">
        <h4 class="card-title">Artifacts</h4>

        <table class="table table-bordered text-center">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Cost</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody class="text-center">
                @foreach($artifacts as $artifact)
                    <tr>
                        <td>{{$artifact->name}}</td>
                        <td>{{$artifact->cost}}</td>
                        <td>
                            <a class="btn btn-primary" href="{{route('game.shop.buy.item')}}"
                               onclick="event.preventDefault();
                                             document.getElementById('shop-buy-form-artifact-{{$artifact->id}}').submit();">
                                {{ __('Buy') }}
                            </a>

                            <form id="shop-buy-form-artifact-{{$artifact->id}}" action="{{route('game.shop.buy.item')}}" method="POST" style="display: none;">
                                @csrf

                                <input type="hidden" name="item_id" value={{$artifact->id}} />
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>