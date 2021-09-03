<div>
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <label for="name">Name: </label>
                <input type="text" class="form-control" name="name" value="" wire:model="monster.name">
                @error('monster.name') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
    <hr />
    <div class="row">
        <div class="col-md-3">
            <div class="form-group">
                <label for="health_range">Health Range: </label>
                <input type="text" class="form-control" name="health_range" value="" wire:model="monster.health_range">
                @error('monster.health_range') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="max_level">Max Level </label>
                <input type="number" class="form-control" name="max_level" value="" wire:model="monster.max_level">
                @error('monster.max_level') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="attack_range">Attack Range: </label>
                <input type="text" class="form-control" name="attack_range" value="" wire:model="monster.attack_range">
                @error('monster.attack_range') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="drop_check">Drop Check: </label>
                <input type="number" step="0.01" class="form-control" name="drop_check" value="" wire:model="monster.drop_check">
                @error('monster.drop_check') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
    <hr />
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label for="damage_stat">Damage Stat: </label>

                <select wire:model="monster.damage_stat" class="form-control">
                    <option>Please select</option>
                    <option value="str">Str</option>
                    <option value="dex">Dex</option>
                    <option value="dur">Dur</option>
                    <option value="int">Int</option>
                    <option value="chr">Chr</option>
                    <option value="agi">Agi</option>
                    <option value="focus">Focus</option>
                </select>
                @error('monster.damage_stat') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="strength">Strength: </label>
                <input type="number" class="form-control" name="strength" value="" wire:model="monster.str">
                @error('monster.str') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="dexterity">Dexterity: </label>
                <input type="number" class="form-control" name="dexterity" value="" wire:model="monster.dex">
                @error('monster.dex') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label for="durability">Durability: </label>
                <input type="number" class="form-control" name="durability" value="" wire:model="monster.dur">
                @error('monster.dur') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="charisma">Charisma: </label>
                <input type="number" class="form-control" name="charisma" value="" wire:model="monster.chr">
                @error('monster.chr') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="intelligence">Intelligence: </label>
                <input type="number" class="form-control" name="intelligence" value="" wire:model="monster.int">
                @error('monster.int') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="agility">Agility: </label>
                <input type="number" class="form-control" name="agility" value="" wire:model="monster.agi">
                @error('monster.agi') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="focus">Focus: </label>
                <input type="number" class="form-control" name="focus" value="" wire:model="monster.focus">
                @error('monster.focus') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
    <hr />
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label for="ac">Armour Class: </label>
                <input type="number" class="form-control" name="ac" value="" wire:model="monster.ac">
                @error('monster.ac') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="exp">XP Reward: </label>
                <input type="number" class="form-control" name="exp" value="" wire:model="monster.xp">
                @error('monster.xp') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="gold">Gold Reward: </label>
                <input type="number" class="form-control" name="gold" value="" wire:model="monster.gold">
                @error('monster.gold') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
    <hr />
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <label for="game_map_id">Game Map: </label>
                <select wire:model="monster.game_map_id" class="form-control">
                    <option>Please select</option>
                    @foreach($gameMaps as $gameMap)
                        <option value="{{$gameMap->id}}">{{$gameMap->name}}</option>
                    @endforeach
                </select>
                @error('monster.game_map_id') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
    <hr />
    <div class="row">
        <div class="{{$monster->is_celestial_entity ? 'col-md-3' : 'col-md-12'}}">
            <div class="form-group form-check-inline">
                <input type="checkbox" class="form-check-input" id="monster-is-celestial" wire:model="monster.is_celestial_entity">
                <label class="form-check-label" for="monster-is-celestial">Is Monster a Celestial?</label>
            </div>
        </div>
        <div class="{{$monster->is_celestial_entity ? 'col-md-3' : 'hide'}}">
            <div class="form-group">
                <label for="gold_cost">Gold Cost: </label>
                <input type="number" class="form-control" name="gold_cost" value="" wire:model="monster.gold_cost">
                @error('monster.gold_cost') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="{{$monster->is_celestial_entity ? 'col-md-3' : 'hide'}}">
            <div class="form-group">
                <label for="gold_dust_cost">Gold Dust Cost: </label>
                <input type="number" class="form-control" name="gold_dust_cost" value="" wire:model="monster.gold_cost_cost">
                @error('monster.gold_cost_cost') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="{{$monster->is_celestial_entity ? 'col-md-3' : 'hide'}}">
            <div class="form-group">
                <label for="shards">Shard Reward: </label>
                <input type="number" class="form-control" name="shards" value="" wire:model="monster.shards">
                @error('monster.gold_cost') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
    <hr />
    <div class="row">
        <div class="{{$monster->can_cast ? 'col-md-6' : 'col-md-12'}}">
            <div class="form-group form-check-inline">
                <input type="checkbox" class="form-check-input" id="can_cast" wire:model="monster.can_cast">
                <label class="form-check-label" for="can_cast">Can Cast?</label>
            </div>
        </div>
        <div class="{{$monster->can_cast ? 'col-md-6' : 'hide'}}">
            <div class="form-group">
                <label for="max_spell_damage">Max Cast Amount: </label>
                <input type="number" class="form-control" name="max_spell_damage" value="" wire:model="monster.max_spell_damage">
                @error('monster.gold_cost') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
    <hr />
    <div class="row">
        <div class="{{$monster->can_use_artifacts ? 'col-md-6' : 'col-md-12'}}">
            <div class="form-group form-check-inline">
                <input type="checkbox" class="form-check-input" id="can_use_artifacts" wire:model="monster.can_use_artifacts">
                <label class="form-check-label" for="can_use_artifacts">Can Use Artifacts?</label>
            </div>
        </div>
        <div class="{{$monster->can_use_artifacts ? 'col-md-6' : 'hide'}}">
            <div class="form-group">
                <label for="max_artifact_damage">Max Artifact Damage Amount: </label>
                <input type="number" class="form-control" name="max_artifact_damage" value="" wire:model="monster.max_artifact_damage">
                @error('monster.max_artifact_damage') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
    <hr />
    <div class="row">
        <div class="col-md-6">
            <label for="spell_evasion">Spell Evasion Percentage: </label>
            <input type="number" class="form-control" name="spell_evasion" value="" wire:model="monster.spell_evasion">
            @error('monster.spell_evasion') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
        <div class="col-md-6">
            <label for="artifact_annulment">Artifact Annulment Percentage: </label>
            <input type="number" class="form-control" name="artifact_annulment" value="" wire:model="monster.artifact_annulment">
            @error('monster.artifact_annulment') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
    </div>
</div>
