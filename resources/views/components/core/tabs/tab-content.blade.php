@props([
    'id' => 'first',
    'active' => 'false'
])

@php
    $css = '';

    if ($active !== 'true') {
        $css = 'tw-hidden';
    }
@endphp

<div id="{{$id}}" class="{{'tw-p-4 ' . $css}}">
  {{$slot}}
</div>