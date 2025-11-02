@props([
    "name",
    "label",
    "model" => null,
    "modelKey" => null,
])

@php
  $value = old($name, optional($model)->{$modelKey});
  dump($value);
  $errorId = $name . "-error";
@endphp

<div class="mb-5">
  <label
    for="{{ $name }}"
    class="mb-2 block text-sm font-medium text-gray-600 dark:text-gray-300"
  >
    {{ $label }}
  </label>

  <input
    id="{{ $name }}"
    name="{{ $name }}"
    type="text"
    value="{{ $value }}"
    class="focus:ring-danube-500 focus:border-danube-500 block w-full rounded border border-gray-300 bg-white px-3 py-2 text-gray-900 placeholder-gray-400 focus:ring-2 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:placeholder-gray-500"
    aria-describedby="{{ $errorId }}"
    {{ $attributes  }}
    @error($name)
        aria-invalid="true"
    @enderror
  />

  @error($name)
    <p id="{{ $errorId }}" class="mt-1 text-sm text-red-600 dark:text-red-400">
      {{ $message }}
    </p>
  @enderror
</div>
