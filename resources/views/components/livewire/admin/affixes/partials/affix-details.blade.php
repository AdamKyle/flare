<div>
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label for="item-affix-name">Name: </label>
                <input type="text" class="form-control required" id="item-affix-name" name="name" wire:model="itemAffix.name">
                @error('itemAffix.name') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="item-affix-type">Type: </label>
                <select class="form-control required" name="item-affix-type" wire:model="itemAffix.type">
                    <option value="">Please select</option>
                    @foreach($types as $type)
                        <option value={{$type}}>{{$type}}</option>
                    @endforeach
                </select>
                @error('itemAffix.type') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="item-affix-description">Description: </label>
                <textarea class="form-control required" name="item-affix-description" wire:model="itemAffix.description"></textarea>
                @error('itemAffix.description') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label for="item-affix-intelligence-required">Intelligence Required: </label>
                <input type="number" class="form-control required" id="item-affix-intelligence-required" name="int-required" wire:model="itemAffix.int_required">
                @error('itemAffix.int_required') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="item-affix-skill-level-required">Skill Level Required: </label>
                <input type="number" class="form-control required" id="item-affix-skill-level-required" name="skill-level-required" wire:model="itemAffix.skill_level_required">
                @error('itemAffix.skill_level_required') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="item-affix-description">Skill Level Trivial: </label>
                <input type="number" class="form-control required" id="item-affix-skill-level-trivial" name="skill-level-trivial" wire:model="itemAffix.skill_level_trivial">
                @error('itemAffix.skill_level_trivial') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group form-check-inline">
                <input type="checkbox" class="form-check-input" id="affix-can-drop" wire:model="itemAffix.can_drop">
                <label class="form-check-label" for="affix-can-drop">Can this affix drop?</label>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="item-affix-cost">Cost: </label>
                <input type="number" class="form-control required" id="item-affix-cost" name="name" wire:model="itemAffix.cost">
                @error('itemAffix.cost') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
</div>
