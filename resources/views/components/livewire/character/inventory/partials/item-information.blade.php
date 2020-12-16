<div id="item-id-{{$item->id}}">
  <div id="accordion-{{$item->id}}">
    <div class="card">
      <div class="card-header" id="headingOne">
        <h5 class="mb-0">
          <button class="btn btn-link" data-toggle="collapse" data-target="#collapse-item-information-{{$item->id}}" aria-expanded="true" aria-controls="collapse-item-information-{{$item->id}}">
            Item Information
          </button>
        </h5>
      </div>
    <div id="collapse-item-information-{{$item->id}}" class="collapse" aria-labelledby="headingOne" data-parent="#accordion-{{$item->id}}">
        <div class="card-body">
          @include('game.items.partials.item', [
              'item' => $item
          ])
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header" id="headingOne">
        <h5 class="mb-0">
          <button class="btn btn-link" data-toggle="collapse" data-target="#collapse-market-prices-{{$item->id}}" aria-expanded="true" aria-controls="collapse-market-prices-{{$item->id}}">
            Market Prices
          </button>
        </h5>
      </div>
      <div id="collapse-market-prices-{{$item->id}}" class="collapse" aria-labelledby="headingOne" data-parent="#accordion-{{$item->id}}">
        <div id="item-market-board-{{$item->id}}" data-item-id="{{$item->id}}"></div>
      </div>
    </div>
  </div>
</div>

@push('scripts')
  <script>
    renderBoard('item-market-board-{{$item->id}}');
  </script>
@endpush