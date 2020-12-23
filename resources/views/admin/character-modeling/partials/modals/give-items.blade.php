<div class="modal fade" id="character-inventory-{{$character->id}}" tabindex="-1" role="dialog" aria-labelledby="character-inventory-label" aria-hidden="true">
    <div class="modal-dialog character-inventory-modal" role="document">
        <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="character-inventory-label">Give Items</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="modal-body">
            <div class="alert alert-info mb-2">
                <p>You may assign a total of: {{$character->inventory_max - $character->inventory->slots()->count()}} items.</p>
                <p>
                    These items are applied to the inventory of this character. These items will not be applied when creating snap shots and 
                    will not be affected by you swtiching or using custom snapshots when testing. 
                </p>
            </div>

            @livewire('admin.character-modeling.item-assignment.data-table', [
                'character' => $character
            ])
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-danger" data-dismiss="modal">Cancel</button>
        </div>
        </div>
    </div>
</div>