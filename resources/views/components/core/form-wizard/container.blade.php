@props(['action', 'lastTab', 'modelId' => 0])

<form method="post" action="{{$action}}">
    @csrf

    <input type="hidden" name="id" value="{{$modelId}}" />

    <div class="tabs wizard wizard-style-2">
        {{$slot}}

        <div class="mt-5">
            <div class="btn-group">
                <button type="button" class="hover:bg-blue-700 hover:drop-shadow-md dark:text-white hover:text-gray-300 bg-blue-600 dark:bg-blue-700 text-white dark:hover:text-white font-semibold
      py-2 px-4 rounded-sm drop-shadow-sm mr-2" data-toggle="wizard"
                        data-direction="previous">Previous</button>
                <button type="button" class="hover:bg-blue-700 hover:drop-shadow-md dark:text-white hover:text-gray-300 bg-blue-600 dark:bg-blue-700 text-white dark:hover:text-white font-semibold
      py-2 px-4 rounded-sm drop-shadow-sm mr-2" data-toggle="wizard"
                        data-direction="next">Next</button>

            </div>
            <button type="submit" class="float-right hover:bg-green-700 hover:drop-shadow-md dark:text-white hover:text-gray-300 bg-green-600 dark:bg-green-700 text-white dark:hover:text-white font-semibold
      py-2 px-4 rounded-sm drop-shadow-sm mr-2">Save</button>
        </div>
    </div>
</form>

