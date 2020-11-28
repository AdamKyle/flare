<form action="{{route('game.market.list', [
    'slot' => $slot->id
])}}" method="POST">
    @csrf

    <div class="form-row">
        <div class="form-group col-md-12">
        <label for="sell_for">Sell For (gold)</label>
        <input type="number" class="form-control" id="sell_for" name="sell_for">
        </div>
    </div>
    <button type="submit" class="btn btn-primary">List item</button>
</form>