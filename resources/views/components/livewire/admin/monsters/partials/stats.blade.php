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
                <label for="health_range">Max Health Range: </label>
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
</div>
