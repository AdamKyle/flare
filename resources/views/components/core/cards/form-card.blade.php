@props([
    'css' => ''
])

<form {{ $attributes->class('card mt-5 p-5 md:p-10 ' . $css) }}>
    {{$slot}}
</form>
