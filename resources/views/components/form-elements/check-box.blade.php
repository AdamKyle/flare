@props([
    'name',
    'label',
    'model'    => null,
    'modelKey' => null,
])

@php
    $value   = old($name, optional($model)->{$modelKey} ? '1' : '0');
    $checked = $value === '1';
    $errorId = $name . '-error';
@endphp

<label for="{{ $name }}" class="flex items-start space-x-2 mb-4">
    <input type="hidden" name="{{ $name }}" value="0" />

    <input
      id="{{ $name }}"
      name="{{ $name }}"
      type="checkbox"
      value="1"
      {{ $checked ? 'checked' : '' }}
      class="mt-1 h-4 w-4 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded text-danube-500 focus:ring-danube-500 focus:ring-2"
      aria-describedby="{{ $errorId }}"
      @error($name) aria-invalid="true" @enderror
    />

    <span class="select-none text-gray-800 dark:text-gray-200">{{ $label }}</span>
</label>

@error($name)
<p id="{{ $errorId }}" class="mt-1 text-sm text-red-600 dark:text-red-400">
    {{ $message }}
</p>
@enderror
