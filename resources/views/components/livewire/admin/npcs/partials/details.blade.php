<div>
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label for="name">Name: </label>
                <input type="text" class="form-control" name="name" value="" wire:model="npc.name">
                @error('npc.name') <span class="text-danger">{{ $message }}</span> @enderror
                <small id="nameHelp" class="form-text text-muted">Name will have all spaces stripped upon saving. We also store this name as their "real name".</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="npc-type">NPC Type: </label>
                <select wire:model="npc.type" class="form-control">
                    <option>Please select</option>
                    @foreach($types as $key => $type)
                        <option value="{{$key}}">{{$type}}</option>
                    @endforeach
                </select>
                @error('npc.type') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="game_map_id">Game Map: </label>
                <select wire:model="npc.game_map_id" class="form-control" id="game_map_id">
                    <option>Please select</option>
                    @foreach($gameMaps as $id => $name)
                        <option value="{{$id}}">{{$name}}</option>
                    @endforeach
                </select>
                @error('npc.game_map_id') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
    <hr />
    <div class="row">
        <div class="col-md-6">
            <div class="form-group form-check-inline">
                <input type="checkbox" class="form-check-input" id="npc-moves" wire:model="npc.moves_around_map">
                <label class="form-check-label" for="npc-moves">Moves around map? (once per hour)</label>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group form-check-inline">
                <input type="checkbox" class="form-check-input" id="must-be-at-npc" wire:model="npc.must_be_at_same_location">
                <label class="form-check-label" for="must-be-at-npc">Must be at same location?</label>
            </div>
        </div>
    </div>
    <hr />
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="x-position"> X: <span class="danger">*</span> </label>
                <select class="custom-select form-control required" id="x-position" name="x_position" wire:model="npc.x_position">
                    <option value="">Select X Position</option>
                    @foreach($coordinates['x'] as $coordinate)
                        <option value="{{$coordinate}}">{{$coordinate}}</option>
                    @endforeach
                </select>
                @error('npc.x') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="y-position"> Y: <span class="danger">*</span> </label>
                <select class="custom-select form-control required" id="y-position" name="y_position" wire:model="npc.y_position">
                    <option value="">Select Y Position</option>
                    @foreach($coordinates['y'] as $coordinate)
                        <option value="{{$coordinate}}">{{$coordinate}}</option>
                    @endforeach
                </select>
                @error('npc.y') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
</div>
