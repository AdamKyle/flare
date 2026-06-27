@props([
    'target',
    'isOpen' => 'false',
])

<div
    id="{{$target}}"
    class="{{ 'collapse text-gray-900 dark:text-gray-100 ' . ($isOpen === 'true' ? 'open' : '') }}"
    role="tabpanel"
    aria-labelledby="{{ $target }}-tab"
    aria-hidden="{{ $isOpen === 'true' ? 'false' : 'true' }}"
>
    {{$slot}}
</div>
