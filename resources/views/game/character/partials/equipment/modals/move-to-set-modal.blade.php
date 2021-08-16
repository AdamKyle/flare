<div class="modal" id="character-inventory-move-{{$slot->id}}" tabindex="-1" role="dialog" aria-labelledby="UseLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Move to set</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info mb-3 mt-2">
                    <p>Moving an item from your inventory to a set will remove it from your base inventory max count.</p>
                    <p>You can choose any set to move an item into. The set can be equipped if it complies with the rules of sets:</p>
                    <ul>
                        <li>
                            1 Weapon, 1 Shield or 2 Weapons or 1 Bow
                        </li>
                        <li>1 Of each armour (Body, Leggings, Sleeves, Feet, Gloves and Helmet)</li>
                        <li>2 Rings</li>
                        <li>2 Spells (1 Healing, 1 Damage or 2 Healing or 2 Damage)</li>
                        <li>2 Artifacts</li>
                    </ul>
                    <p>Sets may be incomplete, but if they do not conform to the above they will be treated as stash tabs (not equipable).</p>
                    <p>Equipping a set (regardless of if it's incomplete) will replace all currently equipped items.</p>
                    <p>It is suggested, unless you are moving the item to a stash tab, that you equip the full set of items you wish to save
                    and then use the Save as Set button to save that set of equipment.</p>
                </div>
                @php
                    $index = $character->inventorySets->search(function($set) {
                        return $set->is_equipped;
                    })
                @endphp
                @if ($index !== false)
                    <div class="alert alert-warning mb-3">
                        You cannot move items into Set {{$index + 1}} as that set is already equipped.
                    </div>
                @endif
               <form method="POST" action="{{route('game.inventory.move.to.set', ['character' => $character])}}">
                   @csrf

                   <input type="hidden" name="slot_id" value="{{$slot->id}}" />

                   <div class="form-row">
                       <div class="form-group col-md-6">
                           <label for="move-to-set">Select set</label>
                           <select name="move_to_set" id="move-to-set" class="form-control">
                               <option value="">Please select</option>
                               @foreach ($character->inventorySets as $index => $set)
                                   @if (!$set->is_equipped)
                                    <option value="{{$set->id}}">Set {{$index + 1}}</option>
                                   @endif
                               @endforeach
                           </select>
                       </div>
                   </div>

                   <button type="submit" class="btn btn-primary mb-2">
                       Move Item
                   </button>
               </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
