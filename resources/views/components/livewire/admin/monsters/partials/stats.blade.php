<div>
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <label for="name">Name: </label>
                <input type="text" class="form-control" name="name" value="" wire:model="name"> 
                @error('name') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
    <hr />
    <div class="row">
        <div class="col-md-3">
            <div class="form-group">
                <label for="health_range">Max Health Range: </label>
                <input type="text" class="form-control" name="health_range" value="" wire:model="health_range"> 
                @error('health_range') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="max_level">Max Level </label>
                <input type="number" class="form-control" name="max_level" value="" wire:model="max_level"> 
                @error('max_level') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="attack_range">Attack Range: </label>
                <input type="text" class="form-control" name="attack_range" value="" wire:model="attack_range"> 
                @error('attack_range') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="drop_check">Drop Check: </label>
                <input type="number" step="0.01" class="form-control" name="drop_check" value="" wire:model="drop_check">
                @error('drop_check') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
    <hr />
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label for="damage_stat">Damage Stat: </label>

                <select wire:model="damage_stat" class="form-control">
                    <option>Please select</option>
                    <option value="str">Str</option>
                    <option value="dex">Dex</option>
                    <option value="dur">Dur</option>
                    <option value="int">Int</option>
                    <option value="chr">Chr</option>
                </select>
                @error('damage_stat') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="strength">Strength: </label>
                <input type="number" class="form-control" name="strength" value="" wire:model="str"> 
                @error('str') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="dexterity">Dexterity: </label>
                <input type="number" class="form-control" name="dexterity" value="" wire:model="dex"> 
                @error('dex') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label for="durabillity">Durabillity: </label>
                <input type="number" class="form-control" name="durabillity" value="" wire:model="dur"> 
                @error('dur') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="charisma">Charisma: </label>
                <input type="number" class="form-control" name="charisma" value="" wire:model="chr"> 
                @error('chr') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="intelligence">Intelligence: </label>
                <input type="number" class="form-control" name="intelligence" value="" wire:model="int"> 
                @error('int') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
    <hr />
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label for="ac">Armour Class: </label>
                <input type="number" class="form-control" name="ac" value="" wire:model="ac"> 
                @error('ac') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="exp">XP Reward: </label>
                <input type="number" class="form-control" name="exp" value="" wire:model="xp"> 
                @error('xp') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="gold">Gold Reward: </label>
                <input type="number" class="form-control" name="gold" value="" wire:model="gold"> 
                @error('gold') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
</div>
