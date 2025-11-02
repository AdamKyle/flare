@props([
  'attributes' => '',
  'css' => '',
])

<button
  type="button"
  {{ $attributes }}
  class="{{ $css }} w-full rounded-md bg-rose-600 px-4 py-2 font-semibold text-white drop-shadow-sm transition-colors transition-shadow hover:bg-rose-700 hover:drop-shadow-md focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 focus:outline-none dark:bg-rose-700 dark:hover:bg-rose-600 dark:focus:ring-offset-gray-800"
>
  {{ $slot }}
</button>
