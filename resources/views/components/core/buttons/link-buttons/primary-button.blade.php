@props([
    'attributes' => '',
    'href'       => '#',
    'css'        => ''
])

<a class="{{'hover:bg-blue-700 hover:drop-shadow-md dark:text-white hover:text-gray-300 bg-blue-600 dark:bg-blue-700 text-white dark:hover:text-white font-semibold
  py-2 px-4 rounded-sm drop-shadow-sm ' . $css}}"
   href="{{$href}}"
   {{$attributes}}
>
  {{ $slot }}
</a>
