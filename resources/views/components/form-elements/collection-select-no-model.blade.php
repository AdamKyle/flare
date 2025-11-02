<div class="mb-5">
  <label class="label mb-2 block" for="{{ $name }}">{{ $label }}</label>
  <select class="form-control" name="{{ $name }}" multiple>
    @foreach ($options as $option)
      @php
        $optionValue = $option->{$value};
        $optionTitle = $option->{$key};
      @endphp

      <option
        value="{{ $optionValue }}"
        {{ ! empty($relationIds) ? (in_array($optionValue, $relationIds) ? 'selected' : '') : '' }}
      >
        {{ $optionTitle }}
      </option>
    @endforeach
  </select>
</div>
