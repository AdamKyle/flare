<input type="hidden" name="slot_id" value={{$slotId}} />
<input type="hidden" name="equip_type" value={{$itemToEquip->type}} />

<fieldset class="form-group row">
    <legend class="col-sm-2">Which Position</legend>
    <div class="col-sm-10">
    <div class="form-check">
        <label class="form-check-label">
            <input class="form-check-input radio-inline" type="radio" name="position" id="position-left" value="spell-one">
            Spell One 
            
            @if (!empty($details))
                @if (isset($details['spell-one']))
                <i class="fas fa-check text-success ml-2"></i> <em><x-item-display-color :item="$details['spell-one']['slot']->item" /> will be replaced.</em>
                @endif
            @endif
        </label>
    </div>
    <div class="form-check">
        <label class="form-check-label">
            <input class="form-check-input radio-inline" type="radio" name="position" id="position-right" value="spell-two">
            Spell Two

            @if (!empty($details))
                @if (isset($details['spell-two']))
                <i class="fas fa-check text-success ml-2"></i> <em><x-item-display-color :item="$details['spell-two']['slot']->item" /> will be replaced.</em>
                @endif
            @endif
        </label>
    </div>
</fieldset>