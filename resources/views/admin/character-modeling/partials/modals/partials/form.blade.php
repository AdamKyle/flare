<form method="POST" action="{{$route}}">
    @csrf

    <input type="hidden" name="model_id" value="{{$model->id}}" />
    <input type="hidden" name="type" value="{{$type}}" />

    <div class="form-row">
        <div class="form-group col-md-6">
            <label for="characters">Characters To Test With</label>
            <select id="characters" class="form-control" name="characters[]" multiple>
                @foreach($users as $user)
                    @if (!is_null($user->character))
                        <option value="{{$user->character->id}}">{{$user->character->name}} {{$user->character->class->name}} - {{$user->character->race->name}}</option>
                    @endif
                @endforeach
            </select>
        </div>
        <div class="form-group col-md-3">
            <label for="level">Test At Level</label>
            <input type="number" class="form-control" id="level" name="character_levels" />
        </div>
        <div class="form-group col-md-3">
            <label for="how-many">How many fights?</label>
            <input type="number" class="form-control" id="how-many" name="total_times" value="{{$type === 'adventure' ? 1 : ''}}" {{$type === 'adventure' ? 'readonly' : '' }}/>
        </div>
    </div>

    <div class="form-row">
        <div class="form-group col-md-12">
            <button type="submit" class="btn btn-primary">Begin Test</button>
        </div>
    </div>
</form>