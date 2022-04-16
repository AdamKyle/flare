
@props([
    'css' => '',
])

<div class="{{'
    bg-white rounded-md drop-shadow-md p-6 overflow-x-auto
    dark:bg-gray-800 dark:text-white mb-5
    ' . $css}}">
    {{$slot}}
</div>
