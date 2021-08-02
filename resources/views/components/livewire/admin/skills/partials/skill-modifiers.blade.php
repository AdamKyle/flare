<div>
    @error('error')
    <div class="alert alert-danger mb-2">
        {{ $message }}
    </div>
    @enderror

    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label for="skill-base-damage-mod-per-level">Base Damage Modifier Per level: </label>
                <input type="number" steps="0.0001" class="form-control required" id="skill-base-damage-mod-per-level"
                       name="base_damage_mod_per_level"
                       wire:model="skill.base_damage_mod_bonus_per_level" {{$disabledSelection ? 'disabled' : ''}}>
                @error('skill.base_damage_mod_per_level') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="skill-base-healing-mod-per-level">Base Healing Modifier Per level: </label>
                <input type="number" steps="0.0001" class="form-control required" id="skill-base-healing-mod-per-level"
                       name="base_healing_mod_per_level"
                       wire:model="skill.base_healing_mod_bonus_per_level" {{$disabledSelection ? 'disabled' : ''}}>
                @error('skill.base_healing_mod_per_level') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="skill-base-ac-mod-per-level">Base AC Modifier Per level: </label>
                <input type="number" steps="0.0001" class="form-control required" id="skill-base-ac-mod-per-level"
                       name="base_ac_mod_per_level"
                       wire:model="skill.base_ac_mod_bonus_per_level" {{$disabledSelection ? 'disabled' : ''}}>
                @error('skill.base_ac_mod_per_level') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label for="skill-fight-time-out-mod-bonus-per-level">Fight Timeout Modifier Per Level: </label>
                <input type="number" steps="0.0001" class="form-control required"
                       id="skill-fight-time-out-mod-bonus-per-level" name="fight_time_out_mod_bonus_per_level"
                       wire:model="skill.fight_time_out_mod_bonus_per_level" {{$disabledSelection ? 'disabled' : ''}}>
                @error('skill.fight_time_out_mod_bonus_per_level') <span
                    class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="skill-move-time-out-mod-bonus-per-level">Move Timeout Modifier Per level: </label>
                <input type="number" steps="0.0001" class="form-control required"
                       id="skill-move-time-out-mod-bonus-per-level" name="move_time_out_mod_bonus_per_level"
                       wire:model="skill.move_time_out_mod_bonus_per_level" {{$disabledSelection ? 'disabled' : ''}}>
                @error('skill.move_time_out_mod_bonus_per_level') <span
                    class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="skill-skill-bonus-per-level">Skill Bonus Per level: </label>
                <input type="number" steps="0.0001" class="form-control required" id="skill-skill-bonus-per-level"
                       name="skill_bonus_per_level"
                       wire:model="skill.skill_bonus_per_level" {{$disabledSelection ? 'disabled' : ''}}>
                @error('skill.skill_bonus_per_level') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="form-check mb-2">
                <input type="checkbox" class="form-check-input" id="skill-can-train"
                       wire:model="skill.can_train" {{$disabledSelection ? 'disabled' : ''}}>
                <label class="form-check-label" for="skill-can-train">Can Train</label>
            </div>
        </div>
    </div>
    @if (!$disabledSelection)
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="skill-for">Type: </label>
                    <select class="form-control" name="skill_for"
                            wire:model="for" {{$canNotAssignSkill ? 'disabled': ''}}>
                        <option value="">Please select</option>
                        <option value="all">Both Monsters and Characters</option>
                        <option value="only-characters">Only Characters</option>
                        <option value="select-monsters">Select Monsters</option>
                        <option value="select-class">Select Class</option>
                    </select>
                    @error('for') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
            </div>
            @if ($for === 'select-monsters')
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="skill-for-monster">Monsters: </label>
                        <select class="form-control" name="skill_for_monster" wire:model="selectedMonsters"
                                {{$for !== 'select-monsters' ? 'disabled' : ''}} multiple>
                            @foreach($monsters as $monster)
                                <option value={{$monster->id}}>{{$monster->name}}</option>
                            @endforeach
                        </select>
                        @error('monster') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>
            @elseif ($for === 'select-class')
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="skill-for-class">Class: </label>
                        <select class="form-control" name="skill_for_class"
                                wire:model="skill.game_class_id" {{$for !== 'select-class' ? 'disabled' : ''}}>
                            <option value="">Please Select</option>
                            @foreach($gameClasses as $gameClass)
                                <option
                                    value={{$gameClass->id}} {{$gameClass->id === $selectedClass ? 'selected' : ''}}>{{$gameClass->name}}</option>
                            @endforeach
                        </select>
                        @error('skill.game_class_id') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>
            @else
            @endif
        </div>
    @else
        <div class="alert alert-info">
            This skill is being used and cannot be modified in terms of who is using it.
        </div>
    @endif
</div>
