<input type="hidden" name="slot_id" value={{$slotId}} />
<input type="hidden" name="equip_type" value={{$itemToEquip->type}} />

@if ($itemToEquip->type === 'spell-damage')
    <fieldset class="form-group row">
        <legend class="col-sm-2">Which Position</legend>
        <div class="col-sm-10">
        <div class="form-check">
            <label class="form-check-label">
                <input class="form-check-input radio-inline" type="radio" name="position" id="position-left" value="spell_one">
                Spell Slot One
            </label>
        </div>
        <div class="form-check">
            <label class="form-check-label">
                <input class="form-check-input radio-inline" type="radio" name="position" id="position-right" value="spell_two">
                Spell Slot Two
            </label>
        </div>
    </fieldset>
@else
    <fieldset class="form-group row">
        <legend class="col-sm-2">Which Position</legend>
        <div class="col-sm-10">
        <div class="form-check">
            <label class="form-check-label">
                <input class="form-check-input radio-inline" type="radio" name="position" id="position-left" value="spell_one">
                Spell Slot One
            </label>
        </div>
        <div class="form-check">
            <label class="form-check-label">
                <input class="form-check-input radio-inline" type="radio" name="position" id="position-right" value="spell_two">
                Spell Slot Two
            </label>
        </div>
    </fieldset>
@endif