<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="adventure-name">Name: <span class="danger">*</span> </label>
            <input type="text" class="form-control required" id=" adventure-name" name="name" value="{{!is_null($adventure) ? $adventure->name : ''}}">
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="adventure-description">Description: <span class="danger">*</span> </label>
            <textarea class="form-control required" id="adventure-description" name="description">{{!is_null($adventure) ? $adventure->description : ''}}</textarea>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="adventure-location"> Loctations : <span class="danger">*</span> </label>
            <select class="custom-select form-control required" id="adventure-location" name="location_ids[]" multiple>
                @foreach($locations as $id => $name)
                    <option {{!is_null($adventure) ? (!is_null($adventure->locations->where('id', $id)->first()) ? 'selected' : '') : ''}} value="{{$id}}">{{$name}}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="adventure-location"> Monster : <span class="danger">*</span> </label>
            <select class="custom-select form-control required" id="adventure-location" name="monster_ids[]" multiple>
                @foreach($monsters as $id => $name)
                    <option {{!is_null($adventure) ? (!is_null($adventure->monsters->where('id', $id)->first()) ? 'selected' : '') : ''}} value="{{$id}}">{{$name}}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="adventure-item-reward"> Reward Item : </label>
            <select class="custom-select form-control" id="adventure-item-reward" name="reward_item_id">
                    <option value="">Please select</option>
                @foreach($items as $id => $name)
                    <option {{(!is_null($adventure) ? !is_null($adventure->itemReward) : '') ? ($adventure->itemReward->id === $id ? 'selected' : '') : ''}} value="{{$id}}">{{$name}}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="adventure-levels">Levels: <span class="danger">*</span> </label>
            <input type="number" class="form-control required" id=" adventure-levels" name="levels" value={{!is_null($adventure) ? $adventure->levels : ''}}>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="adventure-time-per-level">Time Per Level: <span class="danger">*</span> </label>
            <input type="number" class="form-control required" id="adventure-time-per-level" name="time_per_level" value={{!is_null($adventure) ? $adventure->time_per_level : ''}}>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label for="adventure-gold-rush-chance">Gold Rush Chance: <span class="danger">*</span> </label>
            <input type="number" step="0.01" class="form-control required" id="adventure-gold-rush-chance" name="gold_rush_chance" value={{!is_null($adventure) ? $adventure->gold_rush_chance : ''}}>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="adventure-item-find-chance">Item Find Chance: <span class="danger">*</span> </label>
            <input type="number" step="0.01" class="form-control required" id="adventure-item-find-chance" name="item_find_chance" value={{!is_null($adventure) ? $adventure->item_find_chance : ''}}>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="adventure-skill-exp-bonus">Skill EXP Bonus: <span class="danger">*</span> </label>
            <input type="number" step="0.01" class="form-control required" id="adventure-item-find-chance" name="skill_exp_bonus" value={{!is_null($adventure) ? $adventure->skill_exp_bonus : ''}}>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="adventure-skill-exp-bonus">EXP Bonus: <span class="danger">*</span> </label>
            <input type="number" step="0.01" class="form-control required" id="adventure-item-find-chance"
                   name="exp_bonus" value={{!is_null($adventure) ? $adventure->exp_bonus : ''}}>
        </div>
    </div>
</div>
