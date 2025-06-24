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
      bg-mango-tango-500 hover:bg-mango-tango-600
      dark:bg-mango-tango-600 dark:hover:bg-mango-tango-500
      text-white
      font-semibold
      py-2 px-4
      rounded-md
      drop-shadow-sm hover:drop-shadow-md
      transition-colors transition-shadow
      focus:outline-none focus:ring-2 focus:ring-mango-tango-400 focus:ring-offset-2 dark:focus:ring-offset-gray-800
      disabled:opacity-75 disabled:cursor-not-allowed
      mr-2
      {{ $css }}
    "
>
    {{ $slot }}
</a>