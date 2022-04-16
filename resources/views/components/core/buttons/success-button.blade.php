@props([
'attributes' => '',
'css'        => ''
])

<button class="{{'hover:bg-green-700 hover:drop-shadow-md hover:text-gray-300 bg-green-600 dark:bg-green-700 text-white dark:hover:text-white font-semibold
  py-2 px-4 rounded-sm drop-shadow-sm ' . $css}}"
    {{$attributes}}
>
    {{ $slot }}
</button>
