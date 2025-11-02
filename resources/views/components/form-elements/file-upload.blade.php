<div class="mb-5">
  <label class="label mb-2 block" for="{{ $name }}">{{ $label }}</label>
  <input
    id="{{ $name }}"
    type="file"
    class="form-control"
    name="{{ $name }}"
    value="{{ ! is_null($model) ? $model->{$modelKey} : '' }}"
  />
</div>
