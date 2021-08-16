<div>
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label for="item-name">Name: </label>
                <input type="text" class="form-control required" id="item-name" name="name" wire:model="item.name">
                @error('item.name') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="item-type">Type: </label>
                <select class="form-control required" name="item-type" wire:model="item.type">
                    <option value="">Please select</option>
                    @foreach($types as $type)
                        <option value={{$type}}>{{$type}}</option>
                    @endforeach
                </select>
                @error('item.type') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="item-description">Description: </label>
                <textarea class="form-control required" name="item-description" wire:model="item.description"></textarea>
                @error('item.description') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-3">
            <div class="form-group">
                <label for="item-default-position">Default Position: </label>
                <select class="form-control" name="item-type" wire:model="item.default_position" {{in_array($item->type, $defaultPositions) ? '' : 'disabled'}}>
                    <option value="">Please select</option>
                    @foreach($defaultPositions as $defaultPosition)
                        <option value={{$defaultPosition}}>{{$defaultPosition}}</option>
                    @endforeach
                </select>
                <span class="text-muted">Only needed for armor based items where the player cannot select a position.</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="item-base-damage">Base Damage: </label>
                <input type="number" class="form-control" id="item-base-damage" name="item-base-damage" wire:model="item.base_damage" {{($item->type !== 'quest' && $item->type !== 'shield' && $item->type !== 'spell-healing' && in_array($item->type, $itemsWithOutDefaultPosition)) || $item->type === 'bow' ? '' : 'disabled'}}>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="item-base-ac">Base Ac: </label>
                <input type="number" class="form-control" id="item-base-ac" name="item-base-ac" wire:model="item.base_ac" {{in_array($item->type, $typesThatCanAffectAC) ? '' : 'disabled'}}>
                @if ($item->type === 'artifact')
                    <span class="text-muted">Optional for artifacts</span>
                @endif
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="item-base-healing">Base Healing: </label>
                <input type="number" step="0.01" class="form-control" id="item-base-healing" name="item-base-healing" wire:model="item.base_healing">
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="item-base-spell-evasion">Spell Evasion: </label>
                <input type="number" step="0.01" class="form-control" id="item-base-spell-evasion" name="item-base-spell-evasion" wire:model="item.spell_evasion" {{$item->type !== 'ring' ? 'disabled' : null}}>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="item-base-artifact-annulment">Artifact Annulment: </label>
                <input type="number" class="form-control" id="item-base-artifact-annulment" name="item-base-artifact-annulment" wire:model="item.artifact_annulment" {{$item->type !== 'ring' ? 'disabled' : null}}>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="{{$item->can_resurrect ? 'col-md-2' : 'col-md-12'}}">
            <div class="form-group form-check-inline">
                <input type="checkbox" class="form-check-input" id="item-can-resurrect" wire:model="item.can_resurrect" {{$item->type === 'spell-healing' ? '' : 'disabled'}}>
                <label class="form-check-label" for="item-can-resurrect">Can Resurrect</label>
            </div>
        </div>
        <div class="{{$item->can_resurrect ? 'col-md-10' : 'hide'}}">
            <div class="form-group">
                <label class="form-check-label" for="item-resurrect-chance">Resurrect Chance</label>
                <input type="number" steps="0.01" class="form-control" id="item-base-healing" name="item-resurrect-chance" wire:model="item.resurrection_chance">
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-2">
            <div class="form-group form-check-inline">
                <input type="checkbox" class="form-check-input" id="item-can-craft" wire:model="item.can_craft" {{$item->type !== 'quest' ? '' : 'disabled'}}>
                <label class="form-check-label" for="item-can-craft">Can Craft</label>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="item-crafting-type">Crafting Type: </label>
                <select class="form-control" name="item-crafting-type" wire:model="item.crafting_type" {{$item->can_craft ? '' : 'disabled'}}>
                    <option value="">Please select</option>
                    @foreach($craftingTypes as $type)
                        <option value="{{$type}}">{{$type}}</option>
                    @endforeach
                </select>
                <span class="text-muted">Only needed when the item is craftable.</span><br />
                @error('crafting_type') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="item-skill-level-required">Skill Level Required: </label>
                <input type="number" class="form-control" id="item-skill-level-required" name="item-skill-level-required" wire:model="item.skill_level_required" {{$item->can_craft ? '' : 'disabled'}}>
                <span class="text-muted">Only needed when the item is craftable.</span><br />
                @error('skill_level_required') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="item-skill-level-trivial">Skill Level Trivial: </label>
                <input type="number" class="form-control" id="item-skill-level-trivial" name="item-skill-level-trivial" wire:model="item.skill_level_trivial" {{$item->can_craft ? '' : 'disabled'}}>
                <span class="text-muted">Only needed when the item is craftable.</span><br />
                @error('skill_level_trivial') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label for="item-affects-skill">Affects Skill: </label>
                <select class="form-control" name="item-affects-skill" wire:model="item.skill_name">
                    <option value="">Please select</option>
                    @foreach($skills as $skill)
                        <option value="{{$skill->name}}">{{$skill->name}}</option>
                    @endforeach
                </select>
                <span class="text-muted">Only needed when the item affects a skill.</span><br />
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="item-skill-training-bonus">Skill Training Bonus: </label>
                <input type="number" steps="0.01" class="form-control" id="item-skill-training-bonus" name="item-skill-training-bonus" wire:model="item.skill_training_bonus" {{is_null($item->skill_name) || $item->skill_name === "" ? 'disabled' : ''}}>
                <span class="text-muted">Applies an xp percentage bonus to the skill in question when training.</span><br />
                @error('skill_training_bonus') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="item-skill-bonus">Skill Bonus: </label>
                <input type="number" steps="0.01" class="form-control required" id="item-skill-bonus" name="name" wire:model="item.skill_bonus" {{is_null($item->skill_name) || $item->skill_name === "" ? 'disabled' : ''}}>
                <span class="text-muted">Applies a character roll percentage when using said skill.</span><br />
                @error('skill_bonus') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group form-check-inline">
                <input type="checkbox" class="form-check-input" id="item-can-drop" wire:model="item.can_drop">
                <label class="form-check-label" for="item-can-drop">Can this item drop?</label>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group form-check-inline">
                <input type="checkbox" class="form-check-input" id="craft_only" wire:model="item.craft_only">
                <label class="form-check-label" for="craft_only">Can only craft item?</label>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group form-check-inline">
                <input type="checkbox" class="form-check-input" id="market_sellable" wire:model="item.market_sellable">
                <label class="form-check-label" for="market_sellable">Can this item be sold on the market?</label>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label for="item-cost">Cost: </label>
                <input type="number" class="form-control" id="item-cost" name="item-cost" wire:model="item.cost" {{$item->type !== 'quest' ? '' : 'disabled'}}>
                @error('item.cost') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="gold_dust_cost">Gold Dust Cost: </label>
                <input type="number" class="form-control" id="gold_dust_cost" name="gold_dust_cost" wire:model="item.gold_dust_cost" {{$item->type === 'alchemy' ? '' : 'disabled'}}>
                @error('item.gold_dust_cost') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="shard_cost">Shard Cost: </label>
                <input type="number" class="form-control" id="shards_cost" name="shards_cost" wire:model="item.shards_cost" {{$item->type === 'alchemy' ? '' : 'disabled'}}>
                @error('item.shards_cost') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="form-group form-check-inline">
                <input type="checkbox" class="form-check-input" id="usable" wire:model="item.usable" {{$item->type === 'alchemy' ? '' : 'disabled'}}>
                <label class="form-check-label" for="usable">Can this item be used?</label>
            </div>

            <div class="{{$item->usable ? 'row' : 'hide'}}">
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="lasts_for">Lasts For?: </label>
                        <input type="number" class="form-control" id="lasts_for" name="lasts_for" wire:model="item.lasts_for" {{!$item->damages_kingdoms || is_null($item->damages_kingdoms) ? '' : 'disabled'}}>
                        <small class="form-text text-muted">Number is in minutes.</small>
                        @error('item.lasts_for') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            @if ($showUsabillityError)
                <div class="alert alert-danger mb-2 mt-2">
                    You must choose an option since this is usable.
                </div>
            @endif

            <div class="{{$item->usable ? 'row' : 'hide'}}">
                <div class="col-md-4">
                    <div class="form-group form-check-inline">
                        <input type="checkbox" class="form-check-input" id="stat_increase" wire:model="item.stat_increase" {{!$item->damages_kingdoms || is_null($item->damages_kingdoms) ? '' : 'disabled'}}>
                        <label class="form-check-label" for="stat_increase">Stat Increase?</label>
                    </div>

                    <div class="{{$item->stat_increase ? 'row' : 'hide'}}">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="increase_stat_by">Increases Stat By: </label>
                                <input type="number" steps="0.01" class="form-control" id="increase_stat_by" name="increase_stat_by" wire:model="item.increase_stat_by" {{!$item->damages_kingdoms || is_null($item->damages_kingdoms) ? '' : 'disabled'}}>
                                @error('item.increase_stat_by') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label for="affects_skill_type">Affects Skill: </label>
                        <select class="form-control" name="affects_skill_type" wire:model="item.affects_skill_type" {{!$item->damages_kingdoms || is_null($item->damages_kingdoms) ? '' : 'disabled'}}>
                            <option value="">Please select</option>
                            @foreach($skillTypes as $key => $value)
                                <option value="{{$key}}">{{$value}}</option>
                            @endforeach
                        </select>
                    </div>
                    @if ($affectsSkillError)
                        <div class="alert alert-danger mb-2 mt-2">
                            You must specify how this affects the skill.
                        </div>
                    @endif
                    <div class="{{!is_null($item->affects_skill_type) ? 'row' : 'hide'}}">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="increase_skill_bonus_by">Increases Skill Bonus By: </label>
                                <input type="number" steps="0.01" class="form-control" id="increase_skill_bonus_by" name="increase_skill_bonus_by" wire:model="item.increase_skill_bonus_by" {{!$item->damages_kingdoms || is_null($item->damages_kingdoms) ? '' : 'disabled'}}>
                                @error('item.increase_skill_bonus_by') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="increase_skill_training_bonus_by">Increases Skill Training By: </label>
                                <input type="number" steps="0.01" class="form-control" id="increase_skill_training_bonus_by" name="increase_skill_training_bonus_by" wire:model="item.increase_skill_training_bonus_by" {{!$item->damages_kingdoms || is_null($item->damages_kingdoms) ? '' : 'disabled'}}>
                                @error('item.increase_skill_training_bonus_by') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="{{!is_null($item->affects_skill_type) ? 'row' : 'hide'}}">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="base_damage_mod_bonus">Increases Skill Base Damage By: </label>
                                <input type="number" steps="0.01" class="form-control" id="base_damage_mod_bonus" name="base_damage_mod_bonus" wire:model="item.base_damage_mod_bonus" {{!$item->damages_kingdoms || is_null($item->damages_kingdoms) ? '' : 'disabled'}}>
                                @error('item.base_damage_mod_bonus') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="base_healing_mod_bonus">Increases Skill Base Healing By: </label>
                                <input type="number" steps="0.01" class="form-control" id="base_healing_mod_bonus" name="base_healing_mod_bonus" wire:model="item.base_healing_mod_bonus" {{!$item->damages_kingdoms || is_null($item->damages_kingdoms) ? '' : 'disabled'}}>
                                @error('item.base_healing_mod_bonus') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="base_ac_mod_bonus">Increase Skill Base AC By: </label>
                                <input type="number" steps="0.01" class="form-control" id="base_ac_mod_bonus" name="base_ac_mod_bonus" wire:model="item.base_ac_mod_bonus" {{!$item->damages_kingdoms || is_null($item->damages_kingdoms) ? '' : 'disabled'}}>
                                @error('item.base_ac_mod_bonus') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="{{!is_null($item->affects_skill_type) ? 'row' : 'hide'}}">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="fight_time_out_mod_bonus">Increases Skill Fight Time Out By: </label>
                                <input type="number" steps="0.01" class="form-control" id="fight_time_out_mod_bonus" name="fight_time_out_mod_bonus" wire:model="item.fight_time_out_mod_bonus" {{!$item->damages_kingdoms || is_null($item->damages_kingdoms) ? '' : 'disabled'}}>
                                @error('item.fight_time_out_mod_bonus') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="move_time_out_mod_bonus">Increases Move Time Out By: </label>
                                <input type="number" steps="0.01" class="form-control" id="move_time_out_mod_bonus" name="move_time_out_mod_bonus" wire:model="item.move_time_out_mod_bonus" {{!$item->damages_kingdoms || is_null($item->damages_kingdoms) ? '' : 'disabled'}}>
                                @error('item.move_time_out_mod_bonus') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group form-check-inline">
                        <label for="damages_kingdoms">Damages Kingdom: </label>
                        <input type="checkbox" class="form-check-input" id="damages_kingdoms" wire:model="item.damages_kingdoms">
                    </div>

                    <div class="{{$item->damages_kingdoms ? 'row' : 'hide'}}">
                        <div class="col-md-6">
                            <div class="alert alert-info">
                                Any previous information added such as stat increases or skill type bonuses will be voided upon clicking next.<br />
                                A item that effects a kingdom is a one time use that cannot affect skill progression, stats or last for a period of time.
                            </div>
                            <div class="form-group">
                                <label for="kingdom_damage">Damage %: </label>
                                <input type="number" steps="0.01" class="form-control" id="kingdom_damage" name="kingdom_damage" wire:model="item.kingdom_damage">
                                <small id="emailHelp" class="form-text text-muted">Item will damage everything: buildings, units, morale will be affected</small>
                                @error('item.kingdom_damage') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
