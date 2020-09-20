<div>
    @error('missing')
        <div class="alert alert-danger mb-2">
            <span class="text-danger">{{ $message }}</span>
        </div>
    @enderror

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="quest_item_id">Select Item: </label>

                <select wire:model="monster.quest_item_id" class="form-control">
                    <option>Please select</option>
                    @foreach ($this->questItemList as $item)
                        <option value={{$item->id}}>{{$item->name}}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="quest_item_drop_chance">Quest Item Drop Chance: </label>
                <input type="number" step="0.01" class="form-control" name="base_ac_mod" value="" wire:model="monster.quest_item_drop_chance"> 
            </div>
        </div>
    </div>
</div>