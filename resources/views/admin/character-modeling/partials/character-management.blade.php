<div class="pl-3">
    <h5>Basic Options</h5>
    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#character-inventory-{{$character->id}}">
        Give Items
    </button>

    @include('admin.character-modeling.partials.modals.give-items', [
        'character' => $character
    ])
</div>
<hr />
<div class="pl-3 mt-3">
    <h5>Assign Snap Shot</h5>
    <div class="alert alert-warning">
        <p>When selecting a snap shot to veiw the character in, please note this will be reset when you use this character to test.</p>
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