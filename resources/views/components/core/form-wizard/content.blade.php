@props([
    'target',
    'isOpen' => 'false',
])

<div id="{{$target}}" class="{{($isOpen === 'true' ? 'open' : '')}}">
    {{$slot}}
</div>
