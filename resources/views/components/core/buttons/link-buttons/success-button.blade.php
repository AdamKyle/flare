@props([
    'href'       => '#',
    'css'        => ''
])

<a class="{{'bg-green-600 dark:bg-green-700 text-white dark:text-white font-semibold
  py-2 px-4 rounded-sm drop-shadow-sm hover:bg-green-700 hover:drop-shadow-md dark:hover:text-white hover:text-gray-300 mr-2 ' . $css}}"
   href="{{$href}}"
   {{$attributes}}
>
  {{ $slot }}
</a>
