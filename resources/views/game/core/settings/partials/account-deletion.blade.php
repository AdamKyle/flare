<x-core.cards.card-with-title title="Account Deletion or Reset">
    <div class="flex items-center">
        <x-core.buttons.danger-button
            type="submit"
            data-target="#accountDeletion"
            data-toggle="modal"
        >
            Delete Account
        </x-core.buttons.danger-button>
        <div class="ml-4">
            <x-core.buttons.primary-button
                type="submit"
                data-target="#resetAccount"
                data-toggle="modal"
            >
                Reset Account
            </x-core.buttons.primary-button>
        </div>
    </div>
</x-core.cards.card-with-title>

@include('game.core.settings.partials.modals.account-deletion-modal', ['user' => $user])

@include('game.core.settings.partials.modals.account-reset-modal', ['user' => $user])
