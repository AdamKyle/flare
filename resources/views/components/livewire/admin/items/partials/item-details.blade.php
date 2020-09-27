<div>
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label for="item-name">Name: </label>
                <input type="text" class="form-control required" id="item-name" name="name" wire:model="item.name"> 
                @error('item.name') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="item-type">Type: </label>
                <select class="form-control required" name="item-type" wire:model="item.type">
                    <option value="">Please select</option>
                    @foreach($types as $type)
                        <option value={{$type}}>{{$type}}</option>
                    @endforeach
                </select>
                @error('item.type') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="item-description">Description: </label>
                <textarea class="form-control required" name="item-description" wire:model="item.description"></textarea>
                @error('item.description') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-3">
            <div class="form-group">
                <label for="item-default-position">Default Position: </label>
                <select class="form-control" name="item-type" wire:model="item.default_position">
                    <option value="">Please select</option>
                    @foreach($defaultPositions as $defaultPosition)
                        <option value={{$defaultPosition}}>{{$defaultPosition}}</option>
                    @endforeach
                </select>
                <span class="text-mute">Only needed for armor based items where the player cannot select a position.</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="item-base-damage">Base Damage: </label>
                <input type="number" class="form-control" id="item-base-damage" name="item-base-damage" wire:model="item.base_damage"> 
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="item-base-ac">Base Ac: </label>
                <input type="number" class="form-control" id="item-base-ac" name="item-base-ac" wire:model="item.base_ac"> 
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="item-base-healing">Base Healing: </label>
                <input type="number" class="form-control" id="item-base-healing" name="item-base-healing" wire:model="item.base_healing"> 
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-2">
            <div class="form-group form-check-inline">
                <input type="checkbox" class="form-check-input" id="item-can-craft" wire:models="item.can_craft">
                <label class="form-check-label" for="item-can-craft">Can Craft</label>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="item-crafting-type">Crafting Type: </label>
                <select class="form-control" name="item-crafting-type" wire:model="item.crafting_type">
                    <option value="">Please select</option>
                    @foreach($defaultPositions as $defaultPosition)
                        <option value={{$defaultPosition}}>{{$defaultPosition}}</option>
                    @endforeach
                </select>
                <span class="text-mute">Only needed when the item is craftable.</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="item-skill-level-required">Skill Level Required: </label>
                <input type="number" class="form-control" id="item-skill-level-required" name="item-skill-level-required" wire:model="item.skill_level_required"> 
                <span class="text-mute">Only needed when the item is craftable.</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="item-skill-level-trivial">Skill Level Trivial: </label>
                <input type="number" class="form-control" id="item-skill-level-trivial" name="item-skill-level-trivial" wire:model="item.skill_level_trivial"> 
                <span class="text-mute">Only needed when the item is craftable.</span>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="item-affects-skill">Affects Skill: </label>
                <select class="form-control" name="item-affects-skill" wire:model="item.skill_name">
                    <option value="">Please select</option>
                    @foreach(config('game.skills') as $skill)
                        <option value={{$skill['name']}}>{{$skill['name']}}</option>
                    @endforeach
                </select>
                <span class="text-mute">Only needed when the item affects a skill.</span>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="item-skill-training-bonus">Skill Training Bonus: </label>
                <input type="number" class="form-control" id="item-skill-training-bonus" name="item-skill-training-bonus" wire:model="item.skill_training_bonus"> 
                <span class="text-mute">Only needed when the item affects a skill.</span>
            </div>
        </div>
    </div>
</div>
