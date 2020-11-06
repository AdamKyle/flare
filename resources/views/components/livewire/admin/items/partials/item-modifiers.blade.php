<div>
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label for="item-base-damage-mod">Base Damage Mod: </label>
                <input type="number" steps="0.01" class="form-control" id="item-base-damage-mod" name="item-base-damage-mod" wire:model="item.base_damage_mod"> 
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="item-base-ac-mod">Base AC Mod: </label>
                <input type="number" steps="0.01" class="form-control" id="item-base-ac-mod" name="item-base-ac-mod" wire:model="item.base_ac_mod"> 
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="item-base-healing-mod">Base Healing Mod: </label>
                <input type="number" steps="0.01" class="form-control" id="item-base-healing-mod" name="item-base-healing-mod" wire:model="item.base_healing_mod"> 
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label for="item-str-mod">Str Mod: </label>
                <input type="number" steps="0.01" class="form-control" id="item-str-mod" name="item-str-mod" wire:model="item.str_mod"> 
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="item-dex-mod">Dex Mod: </label>
                <input type="number" steps="0.01" class="form-control" id="item-dex-mod" name="item-dex-mod" wire:model="item.dex_mod"> 
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="item-dur-mod">Dur Mod: </label>
                <input type="number" steps="0.01" class="form-control" id="item-dur-mod" name="item-dur-mod" wire:model="item.dur_mod"> 
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="item-int-mod">Int Mod: </label>
                <input type="number" steps="0.01" class="form-control" id="item-int-mod" name="item-int-mod" wire:model="item.int_mod"> 
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="item-chr-mod">Chr Mod: </label>
                <input type="number" steps="0.01" class="form-control" id="item-chr-mod" name="item-chr-mod" wire:model="item.chr_mod"> 
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <label for="item-int-mod">Effects: </label>
                <select class="form-control" name="item-type" wire:model="item.effect" {{is_null($item) || $item->type !== 'quest' ? 'disabled' : ''}}>
                    <option value="">Please select</option>
                    @foreach($effects as $effect)
                        <option value="{{$effect}}">{{$effect}}</option>
                    @endforeach
                </select>
                <span class="text-muted">Only available for items that are of type: Quest</span> 
            </div>
        </div>
    </div>
</div>
