@props(['action', 'lastTab', 'modelId' => 0])

<form method="post" action="{{$action}}" {{$attributes}}>
    @csrf

    <input type="hidden" name="id" value="{{$modelId}}" />

    <div class="tabs wizard wizard-style-2 text-gray-900 dark:text-gray-100">
        {{$slot}}

        <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-col gap-2 sm:flex-row">
                <button type="button" class="hover:bg-blue-700 hover:drop-shadow-md dark:text-white hover:text-gray-300 bg-blue-600 dark:bg-blue-700 text-white dark:hover:text-white font-semibold
      w-full py-2 px-4 rounded-sm drop-shadow-sm sm:w-auto" data-toggle="wizard"
                        data-direction="previous">Previous</button>
                <button type="button" class="hover:bg-blue-700 hover:drop-shadow-md dark:text-white hover:text-gray-300 bg-blue-600 dark:bg-blue-700 text-white dark:hover:text-white font-semibold
      w-full py-2 px-4 rounded-sm drop-shadow-sm sm:w-auto" data-toggle="wizard"
                        data-direction="next">Next</button>

            </div>
            <button type="submit" class="hover:bg-green-700 hover:drop-shadow-md dark:text-white hover:text-gray-300 bg-green-600 dark:bg-green-700 text-white dark:hover:text-white font-semibold
      w-full py-2 px-4 rounded-sm drop-shadow-sm sm:w-auto">Save</button>
        </div>
    </div>
</form>
