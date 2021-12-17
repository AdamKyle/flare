@props([
    'id' => 'first',
    'active' => 'false'
])

@php
    $css = '';

    if ($active !== 'true') {
        $css = 'hidden';
    }
@endphp

<div id="{{$id}}" class="{{'p-4 ' . $css}}">
  {{$slot}}
</div>