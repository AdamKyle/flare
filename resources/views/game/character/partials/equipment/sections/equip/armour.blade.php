<input type="hidden" name="slot_id" value={{$slotId}} />
<input type="hidden" name="equip_type" value={{$itemToEquip->type}} />
<input type="hidden" name="position" value={{$itemToEquip->default_position}} />

@if ($isShop)
  <input type="hidden" name="item_id_to_buy" value={{$itemToEquip->id}} />
@endif

<p class="text-info">You cannot select the position as this has a default position.<p>