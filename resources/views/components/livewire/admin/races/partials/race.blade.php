<div>
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <label for="race-name">Name: </label>
                <input type="text" class="form-control required" id="race-name" name="race-name" wire:model="race.name">
                @error('race.name') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label for="race-str-mod">Strength Modifier: </label>
                <input type="number" steps="0" class="form-control required" id="race-str-mod" name="race-str-mod" wire:model="race.str_mod">
                @error('race.str_mod') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="race-dex-mod">Dexterity Modifier: </label>
                <input type="number" steps="0" class="form-control required" id="race-dex-mod" name="race-dex-mod" wire:model="race.dex_mod">
                @error('race.dex_mod') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="race-int-mod">Intelligence Modifier: </label>
                <input type="number" steps="0" class="form-control required" id="race-int-mod" name="race-int-mod" wire:model="race.int_mod">
                @error('race.int_mod') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label for="race-dur-mod">Durability Modifier: </label>
                <input type="number" steps="0" class="form-control required" id="race-dur-mod" name="race-dur-mod" wire:model="race.dur_mod">
                @error('race.dur_mod') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="race-chr-mod">Charisma Modifier: </label>
                <input type="number" steps="0" class="form-control required" id="race-chr-mod" name="race-chr-mod" wire:model="race.chr_mod">
                @error('race.chr_mod') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="race-deffense-mod">Defense Modifier: </label>
                <input type="number" steps="0.00" class="form-control required" id="race-deffense-mod" name="race-deffense-mod" wire:model="race.deffense_mod">
                @error('race.defense_mod') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="race-agi-mod">Agility Modifier: </label>
                <input type="number" steps="0" class="form-control required" id="race-agi-mod" name="race-agi-mod" wire:model="race.agi_mod">
                @error('race.agi_mod') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="race-focus-mod">Focus Modifier: </label>
                <input type="number" steps="0" class="form-control required" id="race-focus-mod" name="race-focus-mod" wire:model="race.focus_mod">
                @error('race.focus_mod') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
</div>
