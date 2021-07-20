<div>
    @error('error')
        <div class="alert alert-danger mb-2">
            {{ $message }}
        </div>
    @enderror

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
    @if (!is_null($gameBuilding))
        @if ($gameBuilding->trains_units && $gameUnits->isEmpty())
            <div class="alert alert-warning mb-2 mt-2">
                You don't have any game units to add to this building.
            </div>
        @endif
    @endif
    <div class="row">
        <div class="col-md-2">
            <div class="form-group form-check-inline">
                <input type="checkbox" class="form-check-input" id="gameBuilding-is-wall" wire:model="gameBuilding.is_walls">
                <label class="form-check-label" for="gameBuilding-is-wall">Is this building a wall?</label>
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group form-check-inline">
                <input type="checkbox" class="form-check-input" id="gameBuilding-is-farm" wire:model="gameBuilding.is_farm">
                <label class="form-check-label" for="gameBuilding-is-farm">Is this building a farm?</label>
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group form-check-inline">
                <input type="checkbox" class="form-check-input" id="gameBuilding-is-church" wire:model="gameBuilding.is_church">
                <label class="form-check-label" for="gameBuilding-is-church">Is this building a church?</label>
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group form-check-inline">
                <input type="checkbox" class="form-check-input" id="gameBuilding-is-resource-building" wire:model="gameBuilding.is_resource_building">
                <label class="form-check-label" for="gameBuilding-is-resource-building">Does this building give resources per hour?</label>
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group form-check-inline">
                <input type="checkbox" class="form-check-input" id="gameBuilding-trains-units" wire:model="gameBuilding.trains_units">
                <label class="form-check-label" for="gameBuilding-trains-units">Can this building train units?</label>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="skill-for-monster">Units: </label>
                <select class="form-control" name="units-for-building" wire:model="selectedUnits" {{$this->unit_selection_is_disabled ? 'disabled' : ''}} multiple>
                    @foreach($gameUnits as $gameUnit)
                        <option value={{$gameUnit->id}}>{{$gameUnit->name}}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="gameBuilding-trains-units">Units per level <a href="#" data-toggle="modal" data-target="#exampleModal"><i class="fas fa-info-circle"></i></a></label>
                <input type="number" class="form-control" id="gameBuilding-units-per-level" wire:model="gameBuilding.units_per_level" {{$this->unit_selection_is_disabled  ? 'disabled' : ''}}>
                @error('units_per_level') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="gameBuilding-only_at_level">Only at level</label>
                <input type="number" class="form-control" id="gameBuilding-only_at_level" wire:model="gameBuilding.only_at_level" {{($this->unit_selection_is_disabled && $this->only_at_level_is_disabled)  ? 'disabled' : ''}}>
                @error('only_at_level') <span class="text-danger">{{ $message }}</span> @enderror
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

    <div class="modal" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="exampleModalLabel">Game Units Per Level Help</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <p>Based on the units you selected, we will allow these units to be accessed as specific levels of the building.
              For example if you selected 5 units and entered 5 for the units per level that would mean, you get a unit
              at level 1, 6, 11, 16 and 21.</p>
              <p class="text-danger">Amount of selected units and the units per level can never be greator then the maximum building level.</p>
              <div class="mt2">
                  <h5>Formula used</h5>
                  <p>The following formula is how we determine if what you selected and entered is greator then then building max level</p>
                  <p>(Total Units selected * units per level) - (Total units selected - 1)</p>
                  <p>In our example above the answer would be 21. If your building max level is less then 21, you wont be able to create this building.</p>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
            </div>
          </div>
        </div>
      </div>
</div>
