@props(['action', 'lastTab', 'modelId' => 0])

<form method="post" action="{{ $action }}" {{ $attributes }}>
    @csrf

    <input type="hidden" name="id" value="{{ $modelId }}" />

    <div class="tabs wizard wizard-style-2 text-gray-900 dark:text-gray-100">
        {{ $slot }}

        <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-col gap-2 sm:flex-row">
                <button
                    type="button"
                    class="w-full rounded-sm bg-blue-600 px-4 py-2 font-semibold text-white drop-shadow-sm hover:bg-blue-700 hover:text-gray-300 hover:drop-shadow-md sm:w-auto dark:bg-blue-700 dark:text-white dark:hover:text-white"
                    data-toggle="wizard"
                    data-direction="previous"
                >
                    Previous
                </button>
                <button
                    type="button"
                    class="w-full rounded-sm bg-blue-600 px-4 py-2 font-semibold text-white drop-shadow-sm hover:bg-blue-700 hover:text-gray-300 hover:drop-shadow-md sm:w-auto dark:bg-blue-700 dark:text-white dark:hover:text-white"
                    data-toggle="wizard"
                    data-direction="next"
                >
                    Next
                </button>
            </div>
            <button
                type="submit"
                class="w-full rounded-sm bg-green-600 px-4 py-2 font-semibold text-white drop-shadow-sm hover:bg-green-700 hover:text-gray-300 hover:drop-shadow-md sm:w-auto dark:bg-green-700 dark:text-white dark:hover:text-white"
            >
                Save
            </button>
        </div>
    </div>
</form>
