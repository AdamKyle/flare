<div x-data="{ show: true }" x-show="show"
     class="flex w-full sm:w-1/3 mx-auto justify-between
         items-center bg-red-100 relative text-red-700 py-3 px-3
         rounded-md shadow-sm shadow-red-200 border-solid border-2
         border-red-300 dark:bg-red-200 dark:text-red-700 dark:shadow-gray-900 dark:border-red-400">
    <div>
        {{$slot}}
    </div>
    <div>
        <button type="button" @click="show = false" class="text-gray-800">
            <span class="text-2xl">&times;</span>
        </button>
    </div>
</div>
