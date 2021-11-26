@props([
    'attributes' => '',
    'href'       => '#',
    'css'        => ''
])

<a class="{{'tw-bg-blue-600 tw-text-white tw-font-semibold
  tw-py-2 tw-px-4 tw-rounded-sm tw-drop-shadow-sm hover:tw-bg-blue-700 hover:tw-drop-shadow-md ' . $css}}"
   href="{{$href}}"
   {{$attributes}}
>
  {{ $slot }}
</a>