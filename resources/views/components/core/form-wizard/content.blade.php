@props([
    'target',
    'isOpen' => 'false',
])

<div id="{{$target}}" class="{{'collapse ' . ($isOpen === 'true' ? 'open' : '')}}">
    {{$slot}}
</div>
