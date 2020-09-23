<div class="row">
    <div class="col-md-12">
        <div class="form-group">
            <label for="quest-item">Select item as quest reward:</label>
            <select class="custom-select form-control" id="quest-item" name="quest_item_id" wire:model="location.quest_reward_item_id">
                <option value="">Select Item</option>
                @foreach($items as $id => $name)
                    <option value="{{$id}}">{{$name}}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>
