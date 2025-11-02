@props([
  'href' => '#',
  'attributes' => '',
  'css' => '',
])

<a
  href="{{ $href }}"
  {{ $attributes }}
  role="button"
  class="{{ $css }} mr-2 inline-block w-full rounded-md bg-emerald-600 px-4 py-2 font-semibold text-white drop-shadow-sm transition-colors transition-shadow hover:bg-emerald-700 hover:drop-shadow-md focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-75 sm:w-auto dark:bg-emerald-700 dark:hover:bg-emerald-600 dark:focus:ring-offset-gray-800"
>
  {{ $slot }}
</a>
