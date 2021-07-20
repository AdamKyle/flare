<div class="modal" id="mb-item-id-{{$marketBoard->item_id}}" tabindex="-1" role="dialog" aria-labelledby="mb-item-id-{{$marketBoard->item_id}}Label" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="mb-item-id-{{$marketBoard->item_id}}Label">
            Are you sure?
          </h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to do this?</p>

          <p>You will be removing: <x-item-display-color :item="$marketBoard->item" /> from the market board.</p>

          <p>Current Listing Price: {{$marketBoard->listed_price}}</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-success" onclick="event.preventDefault();
            document.getElementById('delist-item-{{$marketBoard->id}}').submit();"
          >
            Yes
          </button>
          <button type="button" class="btn btn-danger" data-dismiss="modal">Cancel</button>

          <form id="delist-item-{{$marketBoard->id}}" action="{{ route('game.delist.current-listing', [
            'marketBoard' => $marketBoard->id
          ]) }}" method="POST" style="display: none;">
            @csrf
          </form>
        </div>
      </div>
    </div>
  </div>
