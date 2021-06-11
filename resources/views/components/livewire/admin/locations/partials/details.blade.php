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
    </div>
</div>

