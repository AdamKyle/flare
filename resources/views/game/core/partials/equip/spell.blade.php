<input type="hidden" name="slot_id" value={{$slotId}} />
<input type="hidden" name="equip_type" value={{$itemToEquip->type}} />

@if ($itemToEquip->type === 'spell-damage')
    <fieldset class="form-group row">
        <legend class="col-sm-2">Which Position</legend>
        <div class="col-sm-10">
        <div class="form-check">
            <label class="form-check-label">
                <input class="form-check-input radio-inline" type="radio" name="position" id="position-left" value="spell_one">
                @if (isset($details['spell_one']))
                    Spll Slot One <span class={{$details['spell_one']['damage_adjustment'] > 0 ? "text-success" : "text-danger"}}>{{$details['spell_one']['damage_adjustment']}} Ac</span>
                @else
                    Spell Slot One <span class="text-success">{{$itemToEquip->getTotalDamage()}} Damage</span>
                @endif
            </label>
        </div>
        <div class="form-check">
            <label class="form-check-label">
                <input class="form-check-input radio-inline" type="radio" name="position" id="position-right" value="spell_two">
                @if (isset($details['spell_two']))
                    Spell Slot Two <span class={{$details['spell_two']['damage_adjustment'] > 0 ? "text-success" : "text-danger"}}>{{$details['spell_two']['damage_adjustment']}} Ac</span>
                @else
                    Spell Slot Two <span class="text-success">{{$itemToEquip->getTotalDamage()}} Damage</span>
                @endif
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
                @if (isset($details['spell_one']))
                    Spll Slot One <span class={{$details['spell_one']['healing_adjustment'] > 0 ? "text-success" : "text-danger"}}>{{$details['spell_one']['healing_adjustment']}} Ac</span>
                @else
                    Spell Slot One <span class="text-success">{{$itemToEquip->getTotalHealing()}} Healing</span>
                @endif
            </label>
        </div>
        <div class="form-check">
            <label class="form-check-label">
                <input class="form-check-input radio-inline" type="radio" name="position" id="position-right" value="spell_two">
                @if (isset($details['spell_two']))
                    Spell Slot Two <span class={{$details['spell_two']['healing_adjustment'] > 0 ? "text-success" : "text-danger"}}>{{$details['spell_two']['healing_adjustment']}} Ac</span>
                @else
                    Spell Slot Two <span class="text-success">{{$itemToEquip->getTotalHealing()}} Healing</span>
                @endif
            </label>
        </div>
    </fieldset>
@endif