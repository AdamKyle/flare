<div>
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label for="item-affix-base-damage-mod">Base Damage Mod: </label>
                <input type="number" steps="0.01" class="form-control" id="item-affix-base-damage-mod" name="item-affix-base-damage-mod" wire:model="itemAffix.base_damage_mod"> 
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="item-affix-base-ac-mod">Base AC Mod: </label>
                <input type="number" steps="0.01" class="form-control" id="item-affix-base-ac-mod" name="item-affix-base-ac-mod" wire:model="itemAffix.base_ac_mod"> 
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="item-affix-base-healing-mod">Base Healing Mod: </label>
                <input type="number" steps="0.01" class="form-control" id="item-affix-base-healing-mod" name="item-affix-base-healing-mod" wire:model="itemAffix.base_healing_mod"> 
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label for="item-affix-str-mod">Str Mod: </label>
                <input type="number" steps="0.01" class="form-control" id="item-affix-str-mod" name="item-affix-str-mod" wire:model="itemAffix.str_mod"> 
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="item-affix-dex-mod">Dex Mod: </label>
                <input type="number" steps="0.01" class="form-control" id="item-affix-dex-mod" name="item-affix-dex-mod" wire:model="itemAffix.dex_mod"> 
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="item-affix-dur-mod">Dur Mod: </label>
                <input type="number" steps="0.01" class="form-control" id="item-affix-dur-mod" name="item-affix-dur-mod" wire:model="itemAffix.dur_mod"> 
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="item-affix-int-mod">Int Mod: </label>
                <input type="number" steps="0.01" class="form-control" id="item-affix-int-mod" name="item-affix-int-mod" wire:model="itemAffix.int_mod"> 
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="item-affix-chr-mod">Chr Mod: </label>
                <input type="number" steps="0.01" class="form-control" id="item-affix-chr-mod" name="item-affix-chr-mod" wire:model="itemAffix.chr_mod"> 
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="item-affix-affix-skill-name">Type: </label>
                <select class="form-control required" name="item-affix-affix-skill-name" wire:model="itemAffix.skill_name">
                    <option value="">Please select</option>
                    @foreach(config('game.skills') as $skill)
                        <option value={{$skill['name']}}>{{$skill['name']}}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="item-affix-affix-cost">Skill Training Bonus: </label>
                <input type="number" steps="0.01" class="form-control required" id="item-affix-affix-cost" name="name" wire:model="itemAffix.skill_training_bonus"> 
                @error('skill_training_bonus') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
</div>
