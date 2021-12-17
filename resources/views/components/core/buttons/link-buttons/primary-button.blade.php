@props([
    'attributes' => '',
    'href'       => '#',
    'css'        => ''
])

<a class="{{'bg-blue-600 text-white font-semibold
  py-2 px-4 rounded-sm drop-shadow-sm hover:bg-blue-700 hover:drop-shadow-md ' . $css}}"
   href="{{$href}}"
   {{$attributes}}
>
  {{ $slot }}
</a>