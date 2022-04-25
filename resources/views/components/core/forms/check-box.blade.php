<label class="custom-checkbox mb-5" for="{{$name}}">
    <input type="checkbox" id="{{$name}}" name="{{$name}}" {{!is_null($model) ? $model->{$modelKey} ? 'checked' : '' : ''}}>
    <span></span>
    <span>{{$label}}</span>
</label>
