<div id="accordion">
    <div class="card">
        <div class="card-header" id="item-info">
          <h5 class="mb-0">
            <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapseOne" aria-expanded="false" aria-controls="collapseTwo">
              Item Information
            </button>
          </h5>
        </div>
        <div id="collapseOne" class="collapse" aria-labelledby="item-info" data-parent="#accordion">
          <div class="card-body">
            @include('game.items.partials.item', [
              'item' => $item
          ])
          </div>
        </div>
      </div>
    <div class="card">
      <div class="card-header" id="current-market-rates">
        <h5 class="mb-0">
          <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
            Current Marketboard Rates
          </button>
        </h5>
      </div>
      <div id="collapseTwo" class="collapse" aria-labelledby="current-market-rates" data-parent="#accordion">
        <div class="card-body">
          <div id="market-info" data-item-id="{{$item->id}}"></div>
        </div>
      </div>
    </div>
    <div class="card">
      <div class="card-header" id="market-history-info">
        <h5 class="mb-0">
          <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
            Market History
          </button>
        </h5>
      </div>
      <div id="collapseThree" class="collapse" aria-labelledby="market-history-info" data-parent="#accordion">
        <div class="card-body">
            <div id="market-history" data-item-id="{{$item->id}}"></div>
        </div>
      </div>
    </div>
  </div>