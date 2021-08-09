<div class="modal" id="character-{{$character->id}}" tabindex="-1" role="dialog" aria-labelledby="UseLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Save As Set</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info mb-3 mt-2">
                    <p>You may only save equipment sets (that is items currently equipped) into empty inventory sets.</p>
                    <p>Saving this as a set will keep it equipped to you.</p>
                </div>

               <form method="POST" action="{{route('game.inventory.save.as.set', ['character' => $character])}}">
                   @csrf

                   <div class="form-row">
                       <div class="form-group col-md-6">
                           <label for="move-to-set">Select set</label>
                           <select name="move_to_set" id="move-to-set" class="form-control ml-3">
                               <option value="">Please select</option>
                               @foreach ($character->inventorySets as $index => $set)
                                   @if ($set->slots->isEmpty())
                                        <option value="{{$set->id}}">Set {{$index + 1}}</option>
                                   @endif
                               @endforeach
                           </select>
                       </div>
                   </div>

                   <button type="submit" class="btn btn-primary mt-2">
                       Save as new set.
                   </button>
               </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
