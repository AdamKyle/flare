<div class="mb-5">
    <label class="label block mb-2" for="{{$name}}">{{$label}}</label>
    <input id="{{$name}}" type="text" class="form-control" name="{{$name}}" value="{{!is_null($model) ? $model->{$modelKey} : ''}}">
</div>
