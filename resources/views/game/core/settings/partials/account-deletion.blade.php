
<div class="row justify-content-center">
    <div class="col-md-12">
        <x-cards.card-with-title title="Account Deletion">
            <div class="text-center">
                <button
                    class="btn btn-danger btn-md"
                    data-toggle="modal"
                    data-target="#account-deletion"
                >
                    Delete Account
                </button>

                @include('game.core.settings.partials.modals.account-deletion-modal', ['user' => $user])
            </div>
        </x-cards.card-with-title>
    </div>
</div>
