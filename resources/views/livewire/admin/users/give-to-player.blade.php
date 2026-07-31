<section class="space-y-4" aria-labelledby="give-to-player-title">
    <div>
        <h4 id="give-to-player-title" class="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100">
            Give To Player
        </h4>
        <p class="text-sm text-gray-700 dark:text-gray-300">
            Give an item, currency, or kingdom gold bars to {{ $character->name }}.
        </p>
    </div>

    @if ($successMessage)
        <div class="rounded-md border border-green-300 bg-green-50 p-3 text-sm font-medium text-green-800 dark:border-green-700 dark:bg-green-900 dark:text-green-100" role="status" aria-live="polite">
            {{ $successMessage }}
        </div>
    @endif

    @if ($errorMessage)
        <div class="rounded-md border border-red-300 bg-red-50 p-3 text-sm font-medium text-red-800 dark:border-red-700 dark:bg-red-900 dark:text-red-100" role="alert">
            {{ $errorMessage }}
        </div>
    @endif

    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <label for="grant-type" class="mb-2 block text-sm font-semibold text-gray-900 dark:text-gray-100">
                Grant Type
            </label>
            <select id="grant-type" wire:model.live="grantType" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                <option value="item">Item</option>
                <option value="gold">Gold</option>
                <option value="gold_dust">Gold Dust</option>
                <option value="shards">Shards</option>
                <option value="copper_coins">Copper Coins</option>
                <option value="gold_bars">Gold Bars</option>
            </select>
        </div>

        @if ($grantType !== 'item')
            <div>
                <label for="grant-amount" class="mb-2 block text-sm font-semibold text-gray-900 dark:text-gray-100">
                    Amount
                </label>
                <input id="grant-amount" type="number" min="1" wire:model.live="amount" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" />
                @error('amount')
                    <p class="mt-2 text-sm font-medium text-red-700 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror
            </div>
        @endif
    </div>

    @if ($grantType === 'item')
        <div>
            <label for="item-search" class="mb-2 block text-sm font-semibold text-gray-900 dark:text-gray-100">
                Search Item
            </label>
            <input id="item-search" type="search" wire:model.live.debounce.300ms="itemSearch" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" aria-describedby="item-search-help" />
            <p id="item-search-help" class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Type at least two letters to search for an item.
            </p>

            @if ($this->searchResults->isNotEmpty())
                <ul class="mt-3 divide-y divide-gray-200 rounded-md border border-gray-200 dark:divide-gray-700 dark:border-gray-700" aria-label="Item search results">
                    @foreach ($this->searchResults as $item)
                        <li class="flex items-center justify-between gap-3 p-3">
                            <span class="text-sm text-gray-900 dark:text-gray-100">{{ $item->name }}</span>
                            <button type="button" wire:click="selectItem({{ $item->id }})" class="rounded-md bg-primary-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                                Select
                            </button>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($selectedItemName)
                <p class="mt-3 text-sm font-semibold text-gray-900 dark:text-gray-100">
                    Selected item: {{ $selectedItemName }}
                </p>
            @endif

            @error('selectedItemId')
                <p class="mt-2 text-sm font-medium text-red-700 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror
        </div>
    @endif

    <div class="flex">
        <button type="button" wire:click="give" class="ltr:ml-auto rtl:mr-auto rounded-md bg-primary-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
            Give
        </button>
    </div>
</section>
