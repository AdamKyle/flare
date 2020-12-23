<div class="pl-3">
    <h5>Basic Options</h5>
    <button class="btn btn-primary mr-2">Give Items</button>
    <button class="btn btn-success mr-2">Create Snapshot</button>
</div>
<hr />
<div class="pl-3 mt-3">
    <h5>Assign Snap Shot</h5>
    <div class="alert alert-warning">
        <p>When you test a monster, if you select this character, any applied snap shots will be overwritten.</p>
        <p>You can also select to use a custom snap shot.</p>
    </div>
    <form action="{{route('admin.character.modeling.assign-snap-shot', [
        'character' => $character
    ])}}" method="POST">
        @csrf
        <div class="form-row">
            <div class="form-group col-md-12">
                <label for="characters">Snap Shot</label>
                <select id="characters" class="form-control" name="snap_shot">
                    <option value="">Please select ...</option>
                    @foreach($character->snapShots as $snapShot)
                        <option value="{{$snapShot->id}}">Level: {{$snapShot->snap_shot['level']}}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-12">
                <button type="submit" class="btn btn-primary">Apply Snapshot</button>
            </div>
        </div>
    </form>
</div>