@props([
    'attributes' => '',
    'css'        => '',
])

<button
  type="button"
  {{ $attributes }}
  class="
      w-full sm:w-auto
      bg-rose-600 hover:bg-rose-700
      dark:bg-rose-700 dark:hover:bg-rose-600
      text-white
      font-semibold
      py-2 px-4
      rounded-md
      drop-shadow-sm hover:drop-shadow-md
      transition-colors transition-shadow
      focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800
      {{ $css }}
    "
>
    {{ $slot }}
</button>
