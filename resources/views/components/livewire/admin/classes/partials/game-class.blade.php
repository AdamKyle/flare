<div>
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <label for="gameClass-name">Name: </label>
                <input type="text" class="form-control required" id="gameClass-name" name="gameClass-name" wire:model="gameClass.name">
                @error('gameClass.name') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label for="gameClass-damage-stat">Damage Stat: </label>
                <select class="form-control required" id="gameClass-damage-stat" name="gameClass-damage-stat" wire:model="gameClass.damage_stat">
                    <option value="">Please Select</option>
                    <option value="str">Str</option>
                    <option value="dex">Dex</option>
                    <option value="dur">Dur</option>
                    <option value="int">Int</option>
                    <option value="chr">Chr</option>
                </select>
                @error('gameClass.damage_stat') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label for="gameClass-to-hit-stat">To Hit Stat: </label>
                <select class="form-control required" id="gameClass-to-hit-stat" name="gameClass-damage-stat" wire:model="gameClass.to_hit_stat">
                    <option value="">Please Select</option>
                    <option value="str">Str</option>
                    <option value="dex">Dex</option>
                    <option value="dur">Dur</option>
                    <option value="int">Int</option>
                    <option value="chr">Chr</option>
                </select>
                @error('gameClass.to_hit_stat') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label for="gameClass-str-mod">Strength Modifier: </label>
                <input type="number" steps="0" class="form-control required" id="gameClass-str-mod" name="gameClass-str-mod" wire:model="gameClass.str_mod">
                @error('gameClass.str_mod') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="gameClass-dex-mod">Dexterity Modifier: </label>
                <input type="number" steps="0" class="form-control required" id="gameClass-dex-mod" name="gameClass-dex-mod" wire:model="gameClass.dex_mod">
                @error('gameClass.dex_mod') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="gameClass-int-mod">Intelligence Modifier: </label>
                <input type="number" steps="0" class="form-control required" id="gameClass-int-mod" name="gameClass-int-mod" wire:model="gameClass.int_mod">
                @error('gameClass.int_mod') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label for="gameClass-dur-mod">Durability Modifier: </label>
                <input type="number" steps="0" class="form-control required" id="gameClass-dur-mod" name="gameClass-dur-mod" wire:model="gameClass.dur_mod">
                @error('gameClass.dur_mod') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="gameClass-chr-mod">Charisma Modifier: </label>
                <input type="number" steps="0" class="form-control required" id="gameClass-chr-mod" name="gameClass-chr-mod" wire:model="gameClass.chr_mod">
                @error('gameClass.chr_mod') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="gameClass-defense-mod">Defense Modifier: </label>
                <input type="number" steps="0.00" class="form-control required" id="gameClass-defense-mod" name="gameClass-defense-mod" wire:model="gameClass.defense_mod">
                @error('gameClass.defense_mod') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="gameClass-agi-mod">Agility Modifier: </label>
                <input type="number" steps="0" class="form-control required" id="gameClass-agi-mod" name="gameClass-agi-mod" wire:model="gameClass.agi_mod">
                @error('gameClass.agi_mod') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="gameClass-focus-mod">Focus Modifier: </label>
                <input type="number" steps="0" class="form-control required" id="gameClass-focus-mod" name="gameClass-focus-mod" wire:model="gameClass.focus_mod">
                @error('gameClass.focus_mod') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
</div>
