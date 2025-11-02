@props([
  'href' => '#',
  'attributes' => '',
  'css' => '',
])

<a
  href="{{ $href }}"
  {{ $attributes }}
  role="button"
  class="bg-mango-tango-500 hover:bg-mango-tango-600 dark:bg-mango-tango-600 dark:hover:bg-mango-tango-500 focus:ring-mango-tango-400 {{ $css }} mr-2 inline-block w-full rounded-md px-4 py-2 font-semibold text-white drop-shadow-sm transition-colors transition-shadow hover:drop-shadow-md focus:ring-2 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-75 sm:w-auto dark:focus:ring-offset-gray-800"
>
  {{ $slot }}
</a>
