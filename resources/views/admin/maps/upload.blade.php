@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Upload Map</h4>
                    <form class="mt-4" action="{{route('upload.map')}}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label for="name">Map Name</label>
                            <input type="text" class="form-control" id="name" aria-describedby="name" name="name">
                        </div>
                        <div class="form-group">
                            <label for="kingdomColor">Kingdom Color</label>
                            <input type="text" class="form-control" id="kingdomColor" aria-describedby="kingdom_name" name="kingdom_color">
                        </div>
                        <fieldset class="form-group row">
                          <legend class="col-sm-2">Default Map?</legend>
                          <div class="col-sm-10">
                            <div class="form-check">
                                <label class="form-check-label">
                                  <input class="form-check-input radio-inline" type="radio" name="default" id="default-yes" value="yes">
                                    Yes
                                </label>
                            </div>
                            <div class="form-check">
                                <label class="form-check-label">
                                    <input class="form-check-input radio-inline" type="radio" name="default" id="default-no" value="no">
                                    No
                                </label>
                            </div>
                        </fieldset>
                        <div class="form-group">
                            <label for="map">Map</label>
                            <input type="file" class="form-control" id="map" aria-describedby="map" name="map">
                        </div>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
