<input type="hidden" name="slot_id" value={{$slotId}} />
<input type="hidden" name="equip_type" value={{$type}} />

@if ($isShop)
    <input type="hidden" name="item_id_to_buy" value={{$item->id}} />
@endif

<fieldset class="form-group row">
    <legend class="col-sm-2">Which Position</legend>
    <div class="col-sm-10">
    <div class="form-check">
        <label class="form-check-label">
            <input class="form-check-input radio-inline" type="radio" name="position" id="position-right" value="ring-one">
            Ring One (Right Finger)

            @if (!empty($details))
                @if (isset($details['ring-one']))
                <i class="fas fa-check text-success ml-2"></i> <em><x-item-display-color :item="$details['ring-one']['slot']->item" /> will be replaced.</em>
                @endif
            @endif
        </label>
    </div>
    <div class="form-check">
        <label class="form-check-label">
            <input class="form-check-input radio-inline" type="radio" name="position" id="position-left" value="ring-two">
            Ring Two (Left Finger)

            @if (!empty($details))
                @if (isset($details['ring-two']))
                <i class="fas fa-check text-success ml-2"></i> <em><x-item-display-color :item="$details['ring-two']['slot']->item" /> will be replaced.</em>
                @endif
            @endif
        </label>
    </div>
</fieldset>
