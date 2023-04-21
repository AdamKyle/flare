<label class="custom-checkbox mb-5" for="{{$name}}">
    <input type="hidden" name="{{$name}}" value="0"/>
    <input type="checkbox" id="{{$name}}" name="{{$name}}" value="1" {{!is_null($model) ? $model->{$modelKey} ? 'checked' : '' : ''}}>
    <span></span>
    <span>{{$label}}</span>
</label>
