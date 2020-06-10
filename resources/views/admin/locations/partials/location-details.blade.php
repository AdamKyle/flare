<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="location-name">Name: <span class="danger">*</span> </label>
            <input type="text" class="form-control required" id="location-name" name="name"> 
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="location-description">Description: <span class="danger">*</span> </label>
            <textarea class="form-control required" id="location-description" name="description"></textarea> 
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="game-map"> Game Map : <span class="danger">*</span> </label>
            <select class="custom-select form-control required" id="game-map" name="map_id">
                <option value="">Select Map</option>
                @foreach($maps as $id => $name)
                    <option value="{{$id}}">{{$name}}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-6">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="x-position"> X: <span class="danger">*</span> </label>
                    <select class="custom-select form-control required" id="x-position" name="x_position">
                        <option value="">Select X Position</option>
                        @foreach($coordinates['x'] as $coordinate)
                            <option value="{{$coordinate}}">{{$coordinate}}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="y-position"> Y: <span class="danger">*</span> </label>
                    <select class="custom-select form-control required" id="y-position" name="y_position">
                        <option value="">Select Y Position</option>
                        @foreach($coordinates['y'] as $coordinate)
                            <option value="{{$coordinate}}">{{$coordinate}}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>