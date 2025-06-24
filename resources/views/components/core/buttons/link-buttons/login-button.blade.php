@props([
    'href'       => '#',
    'attributes' => '',
    'css'        => '',
])

<a
  href="{{ $href }}"
  {{ $attributes }}
  role="button"
  class="
      w-full sm:w-auto inline-block
      bg-regent-st-blue-600 hover:bg-regent-st-blue-700
      dark:bg-regent-st-blue-700 dark:hover:bg-regent-st-blue-600
      text-white font-semibold
      py-2 px-4
      rounded-md
      drop-shadow-sm hover:drop-shadow-md
      transition-colors transition-shadow
      focus:outline-none focus:ring-2 focus:ring-regent-st-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800
      disabled:opacity-75 disabled:cursor-not-allowed
      {{ $css }}
    "
>
    {{ $slot }}
</a>