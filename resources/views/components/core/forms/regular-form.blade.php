@props([
    'css' => ''
])

<form {{ $attributes->class($css) }}>
    {{$slot}}
</form>
