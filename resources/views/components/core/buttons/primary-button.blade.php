@props([
  'css' => '',
])

<button
  {{ $attributes }}
  class="bg-danube-600 hover:bg-danube-700 dark:bg-danube-700 dark:hover:bg-danube-600 focus:ring-danube-500 {{ $css }} w-full rounded-md px-4 py-2 font-semibold text-white drop-shadow-sm transition-colors transition-shadow hover:drop-shadow-md focus:ring-2 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-75 dark:focus:ring-offset-gray-800"
>
  {{ $slot }}
</button>
