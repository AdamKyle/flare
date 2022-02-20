
@props([
    'css' => '',
])

<div class="{{'
    bg-white rounded-sm drop-shadow-sm p-6 overflow-x-auto
    dark:bg-gray-900 dark:text-white
    ' . $css}}">
    {{$slot}}
</div>
