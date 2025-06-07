<div class="mb-5">
    <label class="label block mb-2" for="{{ $name }}">{{ $label }}</label>
    <select class="form-control" name="{{ $name }}">
        <option value="">Please select</option>
        @foreach ($options as $key => $value)
            <option
                value="{{ $key }}"
                {{ ! is_null($model) ? ($model->{$modelKey} === $key ? 'selected' : '') : '' }}
            >
                {{ $value }}
            </option>
        @endforeach
    </select>
</div>
