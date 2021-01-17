<div>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="gameBuilding-name">Name: </label>
                <input type="text" class="form-control required" id="gameBuilding-name" name="gameBuilding-name" wire:model="gameBuilding.name"> 
                @error('gameBuilding.name') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="gameBuilding-description">Description: </label>
                <textarea class="form-control required" id="gameBuilding-description" name="gameBuilding-description" wire:model="gameBuilding.description"></textarea>
                @error('gameBuilding.description') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="gameBuilding-max-level">Max Level: </label>
                <input type="number" class="form-control required" id="gameBuilding-max-level" name="gameBuilding-max-level" wire:model="gameBuilding.max_level"> 
                @error('gameBuilding.max_level') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="gameBuilding-required-pop">Base Required Pop: </label>
                <input type="number" class="form-control required" id="gameBuilding-required-pop" name="gameBuilding-required-pop" wire:model="gameBuilding.required_population">
                @error('gameBuilding.required_population') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="gameBuilding-base-durability">Base Durability: </label>
                <input type="number" class="form-control required" id="gameBuilding-base-durability" name="gameBuilding-base-durability" wire:model="gameBuilding.base_durability"> 
                @error('gameBuilding.base_durability') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="gameBuilding-base-defense">Base Defense: </label>
                <input type="number" class="form-control required" id="gameBuilding-base-defense" name="gameBuilding-base-defense" wire:model="gameBuilding.base_defence">
                @error('gameBuilding.base_defence') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
    
</div>
