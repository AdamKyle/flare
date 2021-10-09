
@props([
    'css' => '',
])

<div class="{{'tw-bg-white tw-rounded-sm tw-drop-shadow-sm tw-p-6 ' . $css}}">
    {{$slot}}
</div>