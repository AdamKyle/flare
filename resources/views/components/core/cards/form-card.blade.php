@props([
  'attributes' => '',
  'css' => '',
])

<form
  class="{{ 'bg-white rounded-sm drop-shadow-md dark:bg-gray-800 dark:text-gray-400 mt-5 p-5 md:p-10 ' . $css }}"
  {{ $attributes }}
>
  {{ $slot }}
</form>
