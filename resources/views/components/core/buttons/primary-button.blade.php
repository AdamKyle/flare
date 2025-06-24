@props([
    'css'        => '',
])

<button
  {{ $attributes }}
  class="
      w-full sm:w-auto
      bg-danube-600 hover:bg-danube-700
      dark:bg-danube-700 dark:hover:bg-danube-600
      text-white
      font-semibold
      py-2 px-4
      rounded-md
      drop-shadow-sm hover:drop-shadow-md
      transition-colors transition-shadow
      focus:outline-none focus:ring-2 focus:ring-danube-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800
      disabled:opacity-75 disabled:cursor-not-allowed
      {{ $css }}
    "
>
    {{ $slot }}
</button>
