@props([
    "name",
    "label",
    "model" => null,
    "modelKey" => null,
])

@php
  $value = old($name, optional($model)->{$modelKey} ? "1" : "0");
  $checked = $value === "1";
  $errorId = $name . "-error";
@endphp

<label for="{{ $name }}" class="mb-4 flex items-start space-x-2">
  <input type="hidden" name="{{ $name }}" value="0" />

  <input
    id="{{ $name }}"
    name="{{ $name }}"
    type="checkbox"
    value="1"
    {{ $checked ? "checked" : "" }}
    class="text-danube-500 focus:ring-danube-500 mt-1 h-4 w-4 rounded border-gray-300 bg-white focus:ring-2 dark:border-gray-600 dark:bg-gray-700"
    aria-describedby="{{ $errorId }}"
    @error($name)
        aria-invalid="true"
    @enderror
  />

  <span class="text-gray-800 select-none dark:text-gray-200">
    {{ $label }}
  </span>
</label>

@error($name)
  <p id="{{ $errorId }}" class="mt-1 text-sm text-red-600 dark:text-red-400">
    {{ $message }}
  </p>
@enderror
