<div class="mb-5">
    <label class="label block mb-2" for="{{ $name }}">{{ $label }}</label>
    <textarea id="{{ $name }}" class="form-control" name="{{ $name }}">
{{ ! is_null($model) ? trim($model->{$modelKey}) : '' }}</textarea
    >
</div>
