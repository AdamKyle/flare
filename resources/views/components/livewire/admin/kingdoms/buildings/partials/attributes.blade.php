<div>
    <div class="row">
        <div class="col-md-3">
            <div class="form-group">
                <label for="gameBuilding-wood-cost">Cost in Wood: </label>
                <input type="number" class="form-control required" id="gameBuilding-wood-cost" name="gameBuilding-wood-cost" wire:model="gameBuilding.wood_cost"> 
                @error('gameBuilding.wood_cost') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="gameBuilding-clay-cost">Cost in Clay: </label>
                <input type="number" class="form-control required" id="gameBuilding-clay-cost" name="gameBuilding-clay-cost" wire:model="gameBuilding.clay_cost"> 
                @error('gameBuilding.clay_cost') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="gameBuilding-stone-cost">Cost in Stone: </label>
                <input type="number" class="form-control required" id="gameBuilding-stone-cost" name="gameBuilding-stone-cost" wire:model="gameBuilding.stone_cost"> 
                @error('gameBuilding.stone_cost') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="gameBuilding-iron-cost">Cost in Iron: </label>
                <input type="number" class="form-control required" id="gameBuilding-iron-cost" name="gameBuilding-iron-cost" wire:model="gameBuilding.iron_cost"> 
                @error('gameBuilding.iron_cost') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-3">
            <div class="form-group form-check-inline">
                <input type="checkbox" class="form-check-input" id="gameBuilding-is-wall" wire:model="gameBuilding.is_walls">
                <label class="form-check-label" for="gameBuilding-is-wall">Is this building a wall?</label>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group form-check-inline">
                <input type="checkbox" class="form-check-input" id="gameBuilding-is-farm" wire:model="gameBuilding.is_farm">
                <label class="form-check-label" for="gameBuilding-is-farm">Is this building a farm?</label>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group form-check-inline">
                <input type="checkbox" class="form-check-input" id="gameBuilding-is-church" wire:model="gameBuilding.is_church">
                <label class="form-check-label" for="gameBuilding-is-church">Is this building a church?</label>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group form-check-inline">
                <input type="checkbox" class="form-check-input" id="gameBuilding-is-resource-building" wire:model="gameBuilding.is_resource_building">
                <label class="form-check-label" for="gameBuilding-is-resource-building">Does this building give resources per hour?</label>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label for="gameBuilding-wood-cost">Increase in Population: </label>
                <input type="number" min="0" class="form-control required" id="gameBuilding-increase-in-pop" name="gameBuilding-increase-in-pop" wire:model="gameBuilding.increase_population_amount"> 
                @error('gameBuilding.increase_population_amount') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="gameBuilding-increase-morale-amount">Increase in Morale: </label>
                <input type="number" steps="0.01" min="0.0" max="1.0" class="form-control required" id="gameBuilding-increase-morale-amount" name="gameBuilding-increase-morale-amount" wire:model="gameBuilding.increase_morale_amount"> 
                @error('gameBuilding.increase_morale_amount') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="gameBuilding-decrease-morale-amount">Decrease in Morale: </label>
                <input type="number" steps="0.01" min="0.0" max="1.0" class="form-control required" id="gameBuilding-decrease-morale-amount" name="gameBuilding-decrease-morale-amount" wire:model="gameBuilding.decrease_morale_amount"> 
                @error('gameBuilding.decrease_morale_amount') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-3">
            <div class="form-group">
                <label for="gameBuilding-increase-wood-amount">Increase Wood Amount: </label>
                <input type="number" steps="0.01" min="0.0" max="1.0" class="form-control required" id="gameBuilding-increase-wood-amount" name="gameBuilding-increase-wood-amount" wire:model="gameBuilding.increase_wood_amount"> 
                @error('gameBuilding.increase_wood_amount') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="gameBuilding-increase-clay-amount">Increase Clay Amount: </label>
                <input type="number" steps="0.01" min="0.0" max="1.0" class="form-control required" id="gameBuilding-increase-clay-amount" name="gameBuilding-increase-clay-amount" wire:model="gameBuilding.increase_clay_amount"> 
                @error('gameBuilding.increase_clay_amount') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="gameBuilding-increase-stone-amount">Increase Stone Amount: </label>
                <input type="number" steps="0.01" min="0.0" max="1.0" class="form-control required" id="gameBuilding-increase-stone-amount" name="gameBuilding-increase-stone-amount" wire:model="gameBuilding.increase_stone_amount"> 
                @error('gameBuilding.increase_stone_amount') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="gameBuilding-increase-iron-amount">Increase Iron Amount: </label>
                <input type="number" steps="0.01" min="0.0" max="1.0" class="form-control required" id="gameBuilding-increase-iron-amount" name="gameBuilding-increase-iron-amount" wire:model="gameBuilding.increase_iron_amount"> 
                @error('gameBuilding.increase_iron_amount') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="gameBuilding-increase-durability-amount">Increase in Durability: </label>
                <input type="number" steps="0.01" min="0.0" max="1.0" class="form-control required" id="gameBuilding-increase-durability-amount" name="gameBuilding-increase-durability-amount" wire:model="gameBuilding.increase_durability_amount"> 
                @error('gameBuilding.increase_durability_amount') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="gameBuilding-increase-defence-amount">Increase in Defence: </label>
                <input type="number" steps="0.01" min="0.0" max="1.0" class="form-control required" id="gameBuilding-increase-defence-amount" name="gameBuilding-increase-defence-amount" wire:model="gameBuilding.increase_defence_amount"> 
                @error('gameBuilding.increase_defence_amount') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="gameBuilding-time-to-build">Time To Build: </label>
                <input type="number" min="0.0" class="form-control required" id="gameBuilding-time-to-build" name="gameBuilding-time-to-build" wire:model="gameBuilding.time_to_build"> 
                @error('gameBuilding.time_to_build') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="gameBuilding-increase-time-amount">Increase in Time: </label>
                <input type="number" steps="0.01" min="0.0" max="5.0" class="form-control required" id="gameBuilding-increase-time-amount" name="gameBuilding-increase-time-amount" wire:model="gameBuilding.time_increase_amount"> 
                @error('gameBuilding.time_increase_amount') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
</div>
