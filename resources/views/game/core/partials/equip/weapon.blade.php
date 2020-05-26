<input type="hidden" name="slot_id" value={{$slotId}} />
<input type="hidden" name="equip_type" value={{$type}} />

<fieldset class="form-group row">
    <legend class="col-sm-2">Which Position</legend>
    <div class="col-sm-10">
    <div class="form-check">
        <label class="form-check-label">
            <input class="form-check-input radio-inline" type="radio" name="position" id="position-left" value="left-hand">
            @if (isset($details['left-hand']))
                Left Hand <span class={{$details['left-hand']['damage_adjustment'] > 0 ? "text-success" : "text-danger"}}>{{$details['left-hand']['damage_adjustment']}} (Replace)</span>
            @else
                Left Hand <span class="text-success">+{{$itemToEquip->getTotalDamage()}} (Equip)</span>
            @endif
        </label>
    </div>
    <div class="form-check">
        <label class="form-check-label">
            <input class="form-check-input radio-inline" type="radio" name="position" id="position-right" value="right-hand">
            @if (isset($details['right-hand']))
                Right Hand <span class={{$details['right-hand']['damage_adjustment'] > 0 ? "text-success" : "text-danger"}}>{{$details['right-hand']['damage_adjustment']}} (Replace)</span>
            @else
                Right Hand <span class="text-success">{{$itemToEquip->getTotalDamage()}} (Equip)</span>
            @endif
        </label>
    </div>
</fieldset>