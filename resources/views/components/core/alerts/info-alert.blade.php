@props([
'title' => 'Oh Christ Child!',
'icon'  => 'fas fa-exclamation-triangle',
])

<div x-data="{ show: true }" x-show="show"
     class="relative flex items-center justify-between w-full px-3 py-3 mx-auto mt-4 mb-5 text-blue-700 bg-blue-100 border-2 border-blue-300 border-solid rounded-md shadow-sm shadow-blue-200 dark:bg-blue-200 dark:text-blue-700 dark:shadow-gray-900 dark:border-blue-400"
     role="alert">
    <div>
        <p class="mb-5 font-bold text-blue-700 dark:text-blue-800-800"><i class="{{$icon}}"></i> {{$title}}</p>
        {{$slot}}
    </div>
</div>
