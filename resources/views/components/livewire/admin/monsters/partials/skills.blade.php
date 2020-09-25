<div>
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label for="skills">Damage Stat: </label>

                <select wire:model="selectedSkill" class="form-control">
                    <option>Please select</option>
                    @foreach ($this->monster->skills as $skill)
                        <option value={{$skill->id}}>{{$skill->name}}</option>
                    @endforeach
                </select>

                @error('skill') <span class="text-danger">{{ $message }}</span> @enderror

                <button wire:click="editSkill" class="btn btn-primary mt-2">Edit Skill</button>
            </div>
        </div>
    </div>
    <hr />

    @if (!is_null($monsterSkill))
        <h4 class="mb-3">{{$monsterSkill->name}}</h4>
        @if (session()->has('message'))
            <div class="alert alert-success mb-3">
                {{ session('message') }}
            </div>
        @endif
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label for="base_ac_mod">Base AC Modifier: </label>
                    <input type="number" step="0.01" class="form-control" name="base_ac_mod" value="" wire:model="monsterSkill.base_ac_mod"> 
                    @error('monsterSkill.base_ac_mod') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="base_damage_mod">Base Damage Modifier: </label>
                    <input type="number" step="0.01" class="form-control" name="base_damage_mod" value="" wire:model="monsterSkill.base_damage_mod"> 
                    @error('monsterSkill.base_damage_mod') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="base_healing_mod">Base Healing Modifier: </label>
                    <input type="number" step="0.01" class="form-control" name="base_healing_mod" value="" wire:model="monsterSkill.base_healing_mod"> 
                    @error('monsterSkill.base_healing_mod') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="skill_bonus">Skill Bonus: </label>
                    <input type="number" step="0.01" class="form-control" name="skill_bonus" value="" wire:model="monsterSkill.skill_bonus"> 
                    @error('monsterSkill.skill_bonus') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        <hr />
            <button class="btn btn-success" wire:click="save">Save Modifications</button>
        <hr />
    @endif
</div>
