<input type="hidden" name="slot_id" value={{$slotId}} />
<input type="hidden" name="equip_type" value={{$type}} />

<fieldset class="form-group row">
    <legend class="col-sm-2">Which Position</legend>
    <div class="col-sm-10">
    <div class="form-check">
        <label class="form-check-label">
            <input class="form-check-input radio-inline" type="radio" name="position" id="position-left" value="artifact-one">
            Artifact Slot One

            @if (!empty($details))
                @if (isset($details['artifact-one']))
                <i class="fas fa-check text-success ml-2"></i> <em><x-item-display-color :item="$details['artifact-one']['slot']->item" /> will be replaced.</em>
                @endif
            @endif
        </label>
    </div>
    <div class="form-check">
        <label class="form-check-label">
            <input class="form-check-input radio-inline" type="radio" name="position" id="position-right" value="artifact-two">
            Artifact Slot Two

            @if (!empty($details))
                @if (isset($details['artifact-two']))
                <i class="fas fa-check text-success ml-2"></i> <em><x-item-display-color :item="$details['artifact-two']['slot']->item" /> will be replaced.</em>
                @endif
            @endif
        </label>
    </div>
</fieldset>