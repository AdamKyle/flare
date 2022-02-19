<div>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="location-name">Name: </label>
                <input type="text" class="form-control required" id="location-name" name="name" wire:model="location.name">
                @error('location.name') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="location-description">Description: </label>
                <textarea class="form-control required" id="location-description" name="description" wire:model="location.description"></textarea>
                @error('location.description') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label for="game-map">Game Map: </label>
                <select class="custom-select form-control required" id="game-map" name="map_id"  wire:model="location.game_map_id">
                    <option value="">Select Map</option>
                    @foreach($maps as $id => $name)
                        <option value="{{$id}}">{{$name}}</option>
                    @endforeach
                </select>
                @error('location.game_map_id') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group form-check-inline">
                <input type="checkbox" class="form-check-input" id="location-is-port" wire:model="location.is_port">
                <label class="form-check-label" for="location-is-port">Is Port Location?</label>
            </div>
        </div>
        <div class="col-md-4">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="x-position"> X: <span class="danger">*</span> </label>
                        <select class="custom-select form-control required" id="x-position" name="x_position" wire:model="location.x">
                            <option value="">Select X Position</option>
                            @foreach($coordinates['x'] as $coordinate)
                                <option value="{{$coordinate}}">{{$coordinate}}</option>
                            @endforeach
                        </select>
                        @error('location.x') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="y-position"> Y: <span class="danger">*</span> </label>
                        <select class="custom-select form-control required" id="y-position" name="y_position" wire:model="location.y">
                            <option value="">Select Y Position</option>
                            @foreach($coordinates['y'] as $coordinate)
                                <option value="{{$coordinate}}">{{$coordinate}}</option>
                            @endforeach
                        </select>
                        @error('location.y') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="enemy_strength_type">Strength Addition: </label>
                <select class="custom-select form-control required" id="enemy_strength_type" name="enemy_strength_type" wire:model="location.enemy_strength_type">
                    <option value="">Select Strength Type</option>
                    @foreach($locationEffects as $index => $locationEffect)
                        <option value="{{$index}}">{{$locationEffect}}</option>
                    @endforeach
                </select>
                @error('location.enemy_strength_type') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="required_quest_item_id">Quest item required to enter: </label>
                <select class="custom-select form-control required" id="required_quest_item_id" name="required_quest_item_id" wire:model="location.required_quest_item_id">
                    <option value="">Select Quest item</option>
                    @foreach($questItems as $index => $itemName)
                        <option value="{{$index}}">{{$itemName}}</option>
                    @endforeach
                </select>
                @error('location.required_quest_item_id') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <label for="type">Location Type: </label>
                <select class="custom-select form-control required" id="type" name="type" wire:model="location.type">
                    <option value="">Select Type</option>
                    @foreach($locationTypes as $type => $typeName)
                        <option value="{{$type}}">{{$typeName}}</option>
                    @endforeach
                </select>
                @error('location.type') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>

</div>

