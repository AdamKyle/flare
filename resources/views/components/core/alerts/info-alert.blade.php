@props([
'title' => 'Oh Christ Child!',
'icon'  => 'fas fa-exclamation-triangle',
])

<div x-data="{ show: true }" x-show="show"
     class="flex w-full mx-auto justify-between
         items-center bg-blue-100 relative text-blue-700 py-3 px-3
         rounded-md shadow-sm shadow-blue-200 border-solid border-2
         border-blue-300 dark:bg-blue-200 dark:text-blue-700 dark:shadow-gray-900 dark:border-blue-400"
     role="alert">
    <div>
        <p class="font-bold text-blue-700 dark:text-blue-800-800 mb-5"><i class="{{$icon}}"></i> {{$title}}</p>
        {{$slot}}
    </div>
</div>
