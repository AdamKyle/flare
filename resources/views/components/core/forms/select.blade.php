<div class="mb-5">
    <label class="label block mb-2" for="{{ $name }}">{{ $label }}</label>
    <select
        class="form-control"
        name="{{ $name }}"
        id="{{ $name }}"
        value="{{ ! is_null($model) ? $model->{$modelKey} : null }}"
    >
        <option value="">Please select</option>
        @foreach ($options as $option)
            <option
                value="{{ $option }}"
                {{ ! is_null($model) ? ($model->{$modelKey} === $option ? 'selected' : '') : '' }}
            >
                {{ $option }}
            </option>
        @endforeach
    </select>
</div>
