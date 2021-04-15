<div>
    @error('error')
        <div class="alert alert-danger mb-2">
            {{ $message }}
        </div>
    @enderror

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="gameUnit-name">Name: </label>
                <input type="text" class="form-control required" id="gameUnit-name" name="gameUnit-name" wire:model="gameUnit.name">
                @error('gameUnit.name') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="gameUnit-description">Description: </label>
                <textarea class="form-control required" id="gameUnit-description" name="gameUnit-description" wire:model="gameUnit.description"></textarea>
                @error('gameUnit.description') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="gameUnit-attack">Attack: </label>
                <input type="number" class="form-control required" id="gameUnit-attack" name="gameUnit-attack" wire:model="gameUnit.attack">
                @error('gameUnit.attack') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="gameUnit-defence">Defence: </label>
                <input type="number" class="form-control required" id="gameUnit-defence" name="gameUnit-defence" wire:model="gameUnit.defence">
                @error('gameUnit.defence') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-12">
            <h3>
                <span class="header"></span> Unit Attributes <i class="ra ra-crossed-axes ml-2"></i>
            </h3>
            <hr />
        </div>
    </div>
    <div class="row">
        <div class="col-md-2">
            <div class="form-group form-check-inline">
                <input type="checkbox" class="form-check-input" id="gameUnit-can-heal" wire:model="gameUnit.can_heal">
                <label class="form-check-label" for="gameUnit-can-heal">Can this unit heal?</label>
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group form-check-inline">
                <input type="checkbox" class="form-check-input" id="gameUnit-siege-weapon" wire:model="gameUnit.siege_weapon">
                <label class="form-check-label" for="gameUnit-siege-weapon">Is this a seige weapon?</label>
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group form-check-inline">
                <input type="checkbox" class="form-check-input" id="gameUnit-attacker" wire:model="gameUnit.attacker">
                <label class="form-check-label" for="gameUnit-attacker">Attacker?</label>
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group form-check-inline">
                <input type="checkbox" class="form-check-input" id="gameUnit-defender" wire:model="gameUnit.defender">
                <label class="form-check-label" for="gameUnit-defender">Defender?</label>
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group form-check-inline">
                <input type="checkbox" class="form-check-input" id="gameUnit-can-not-be-healed" wire:model="gameUnit.can_not_be_healed">
                <label class="form-check-label" for="gameUnit-can-not-be-healed">Can not be healed?</label>
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group form-check-inline">
                <input type="checkbox" class="form-check-input" id="gameUnit-is-settler" wire:model="gameUnit.is_settler">
                <label class="form-check-label" for="gameUnit-is-settler">Is Settler?</label>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="gameUnit-heal-for">Heals other units for (percentage)?: </label>
                <input type="number" steps="0.01" min="0.0" max="1.0" class="form-control required" id="gameUnit-heal-for" name="gameUnit-heal-for" wire:model="gameUnit.heal_percentage" {{$this->is_heal_for_disabled ? 'disabled' : ''}}>
                @error('gameUnit.heal_percentage') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="gameUnit-reduces-morale-by">Reduces Morale By (percentage)?: </label>
                <input type="number" steps="0.01" min="0.0" max="1.0" class="form-control required" id="gameUnit-reduces-morale-by" name="gameUnit-reduces-morale-by" wire:model="gameUnit.reduces_morale_by" {{$this->is_reduces_morale_by_disabled ? 'disabled' : ''}}>
                @error('gameUnit.reduces_morale_by') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-3">
            <div class="form-group">
                <label for="gameUnit-wood-cost">Cost in Wood: </label>
                <input type="number" class="form-control required" id="gameUnit-wood-cost" name="gameUnit-wood-cost" wire:model="gameUnit.wood_cost">
                @error('gameUnit.wood_cost') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="gameUnit-clay-cost">Cost in Clay: </label>
                <input type="number" class="form-control required" id="gameUnit-clay-cost" name="gameUnit-clay-cost" wire:model="gameUnit.clay_cost">
                @error('gameUnit.clay_cost') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="gameUnit-stone-cost">Cost in Stone: </label>
                <input type="number" class="form-control required" id="gameUnit-stone-cost" name="gameUnit-stone-cost" wire:model="gameUnit.stone_cost">
                @error('gameUnit.stone_cost') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="gameUnit-iron-cost">Cost in Iron: </label>
                <input type="number" class="form-control required" id="gameUnit-iron-cost" name="gameUnit-iron-cost" wire:model="gameUnit.iron_cost">
                @error('gameUnit.iron_cost') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label for="gameUnit-required-population">Required Population: </label>
                <input type="number" min="1" class="form-control required" id="gameUnit-required-population" name="gameUnit-required-population" wire:model="gameUnit.required_population">
                @error('gameUnit.required_population') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="gameUnit-travel-time">Travel Time (minutes): </label>
                <input type="number" min="1" class="form-control required" id="gameUnit-travel-time" name="gameUnit-travel-time" wire:model="gameUnit.travel_time">
                @error('gameUnit.travel_time') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="gameUnit-recruitment-time">Recruitment Time (seconds): </label>
                <input type="number" min="1" class="form-control required" id="gameUnit-recruitment-time" name="gameUnit-recruitment-time" wire:model="gameUnit.time_to_recruit">
                @error('gameUnit.time_to_recruit') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="gameUnit-weak-against-unit-id">Primary Target?: </label>
                <select class="form-control" wire:model="gameUnit.primary_target">
                    <option>Please select</option>
                    <option value="Walls">Walls</option>
                    <option value="Farm">Farm</option>
                    <option value="Buildings">Buildings</option>
                </select>
                @error('gameUnit.primary_target') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="gameUnit-weak-against-unit-id">Fallback Target?: </label>
                <select class="form-control" wire:model="gameUnit.fall_back">
                    <option>Please select</option>
                    <option value="Walls">Walls</option>
                    <option value="Farm">Farm</option>
                    <option value="Buildings">Buildings</option>
                </select>
                @error('gameUnit.fall_back') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
</div>
