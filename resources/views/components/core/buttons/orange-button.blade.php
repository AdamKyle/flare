@props([
  'attributes' => '',
  'css' => '',
])

<button
  type="button"
  {{ $attributes }}
  class="bg-mango-tango-500 hover:bg-mango-tango-600 dark:bg-mango-tango-600 dark:hover:bg-mango-tango-500 focus:ring-mango-tango-400 {{ $css }} w-full rounded-md px-4 py-2 font-semibold text-white drop-shadow-sm transition-colors transition-shadow hover:drop-shadow-md focus:ring-2 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-75 dark:focus:ring-offset-gray-800"
>
  {{ $slot }}
</button>
