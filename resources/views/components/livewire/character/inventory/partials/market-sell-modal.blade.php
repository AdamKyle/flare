<div class="modal fade" id="slot-id-{{$slot->id}}" tabindex="-1" role="dialog" aria-labelledby="slot-id-{{$slot->id}}Label" aria-hidden="true">
    <div class="modal-dialog large-modal" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="slot-id-{{$slot->id}}Label">
            <x-item-display-color :item="$slot->item" />
          </h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            @include('components.livewire.character.inventory.partials.market-form', [
              'slot' => $slot,
            ])
          </div>
          <div class="mb-3 mt-2 alert alert-warning">
            <p>Please note that all transactions come with a 5% tax. This tax is applied when you buy and item
            from the market board and when your item sells.</p>
            <p>All items can be removed from the market, by visiting the board and clicking: remove, while on the market board.</p>
          </div>
          @include('components.livewire.character.inventory.partials.item-information', [
            'item' => $slot->item
          ])
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>
