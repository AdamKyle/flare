<div>
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label for="name">Name: </label>
                <input type="text" class="form-control" name="name" value="" wire:model="quest.name">
                @error('quest.name') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="npc_id">Belongs To NPC: </label>
                <select wire:model="quest.npc_id" class="form-control" id="npc_id">
                    <option>Please select</option>
                    @foreach($npcs as $key => $type)
                        <option value="{{$key}}">{{$type}}</option>
                    @endforeach
                </select>
                @error('quest.npc_id') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="item_id">Required Item </label>
                <select wire:model="quest.item_id" class="form-control" id="item_id">
                    <option>Please select</option>
                    @foreach($items as $id => $name)
                        <option value="{{$id}}">{{$name}}</option>
                    @endforeach
                </select>
                @error('quest.required_item') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
    <hr />
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label for="gold_dust_cost">Gold Dust Cost: </label>
                <input type="number" class="form-control" name="gold_dust_cost" value="" wire:model="quest.gold_dust_cost">
                @error('quest.gold_dust_cost') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="shards_cost">Shards Cost: </label>
                <input type="number" class="form-control" name="shards_cost" value="" wire:model="quest.shards_cost">
                @error('quest.shards_cost') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="gold_cost">Gold Cost: </label>
                <input type="number" class="form-control" name="gold_cost" value="" wire:model="quest.gold_cost">
                @error('quest.gold_cost') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
    <hr />
    <div class="row">
        <div class="col-md-3">
            <div class="form-group">
                <label for="reward_gold_dust">Rewards Gold Dust: </label>
                <input type="number" class="form-control" name="reward_gold_dust" value="" wire:model="quest.reward_gold_dust">
                @error('quest.reward_gold_dust') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="reward_shards">Rewards Shards: </label>
                <input type="number" class="form-control" name="reward_shards" value="" wire:model="quest.reward_shards">
                @error('quest.reward_shards') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="reward_gold">Rewards Gold: </label>
                <input type="number" class="form-control" name="reward_gold" value="" wire:model="quest.reward_gold">
                @error('quest.reward_gold') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="reward_xp">Rewards XP: </label>
                <input type="number" class="form-control" name="reward_xp" value="" wire:model="quest.reward_xp">
                @error('quest.reward_xp') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
    <hr />
    <div class="row">
        <div class="{{$quest->unlocks_skill ? 'col-md-6' : 'col-md-12'}}">
            <div class="form-group form-check-inline">
                <input type="checkbox" class="form-check-input" id="unlocks_skill" wire:model="quest.unlocks_skill">
                <label class="form-check-label" for="unlocks_skill">Unlocks a skill upon completion?</label>
            </div>
        </div>
        <div class="{{$quest->unlocks_skill ? 'col-md-6' : 'hide'}}">
            <div class="form-group">
                <label for="unlocks_skill_type">Unlocks Skill Type </label>
                <select wire:model="quest.unlocks_skill_type" class="form-control" id="unlocks_skill_type">
                    <option>Please select</option>
                    @foreach($skillTypes as $id => $name)
                        <option value="{{$id}}">{{$name}}</option>
                    @endforeach
                </select>
                @error('quest.unlocks_skill_type') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
    <hr />
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <label for="reward_item">Reward (Quest) Item </label>
                <select wire:model="quest.reward_item" class="form-control" id="reward_item">
                    <option>Please select</option>
                    @foreach($items as $id => $name)
                        <option value="{{$id}}">{{$name}}</option>
                    @endforeach
                </select>
                @error('quest.reward_item') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
</div>
