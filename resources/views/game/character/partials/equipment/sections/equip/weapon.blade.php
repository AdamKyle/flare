<input type="hidden" name="slot_id" value={{$slotId}} />
<input type="hidden" name="equip_type" value={{$type === 'bow' ? 'weapon' : $type}} />

@if ($isShop)
    <input type="hidden" name="item_id_to_buy" value={{$item->id}} />
@endif

@if ($item->type === 'bow' || $item->type === 'hammer')
    <x-core.alerts.info-alert title="ATTN!">
        You can choose what ever hand to hold this item in , but you <strong>cannot</strong> duel wield two of these items, because they are two-handed items.
        When using Cast and Attack or Attack and Cast, this weapon will be used regardless of hand.
    </x-core.alerts.info-alert>
@endif

<fieldset class="form-group row">
    <legend class="col-sm-2">Which Position</legend>
    <div class="col-sm-10">
    <div class="form-check">
        <label class="form-check-label">
            <input class="form-check-input radio-inline" type="radio" name="position" id="position-right" value="right-hand">
            Right Hand

            @if (!empty($details))
                @if (isset($details['right-hand']))
                    <i class="fas fa-check text-success ml-2"></i> <em><x-item-display-color :item="$details['right-hand']['slot']->item" /> will be replaced.</em>
                @endif
            @endif
        </label>
    </div>
    <div class="form-check">
        <label class="form-check-label">
            <input class="form-check-input radio-inline" type="radio" name="position" id="position-left" value="left-hand">
            Left Hand

            @if (!empty($details))
                @if (isset($details['left-hand']))
                <i class="fas fa-check text-success ml-2"></i> <em><x-item-display-color :item="$details['left-hand']['slot']->item" /> will be replaced.</em>
                @endif
            @endif
        </label>
    </div>
</fieldset>
