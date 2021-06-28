<div>
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label for="skills">Skill: </label>

                @if (!is_null($this->monster))
                    <select wire:model="selectedSkill" class="form-control">
                        <option>Please select</option>
                        @foreach ($this->monster->skills as $skill)
                            <option value={{$skill->id}}>{{$skill->name}}</option>
                        @endforeach
                    </select>

                    @error('skill') <span class="text-danger">{{ $message }}</span> @enderror
                @endif

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
        <div class="alert alert-info">
            By increasing the level, the stat skills will be calculated automatically.
            If you wish, you can edit the individual base skills that all monsters and characters use
            and change their values from there.
        </div>
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label for="base_ac_mod">Level: </label>
                    <input type="number" step="0.01" class="form-control" name="base_ac_mod" value="" wire:model="monsterSkill.level">
                    @error('monsterSkill.level') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        <hr />
            <button class="btn btn-success" wire:click="save">Save Modifications</button>
        <hr />
    @endif
</div>
