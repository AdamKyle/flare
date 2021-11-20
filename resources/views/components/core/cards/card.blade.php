
@props([
    'css' => '',
])

<div class="{{'tw-bg-white tw-rounded-sm tw-drop-shadow-sm tw-p-6 tw-overflow-x-auto ' . $css}}">
    {{$slot}}
</div>