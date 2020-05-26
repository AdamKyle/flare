<input type="hidden" name="slot_id" value={{$slotId}} />
<input type="hidden" name="equip_type" value={{$itemToEquip->type}} />
<input type="hidden" name="position" value={{$itemToEquip->default_position}} />

<p>Equipping this will increase your total AC by: 
    @if (empty($details)) 
        <span class="text-success">
            {{$itemToEquip->getTotalDefence()}}
        </span>
    @else
        <span class={{$details['left-hand']['ac_adjustment'] > 0 ? "text-success" : "text-danger"}}>
            {{$itemToEquip->getTotalDefence()}}
        </span>
    @endif
</p>