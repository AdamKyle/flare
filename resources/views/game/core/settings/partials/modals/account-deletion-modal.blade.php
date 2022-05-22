<div id="accountDeletion" class="modal" data-animations="fadeInDown, fadeOutUp">
    <div class="modal-dialog max-w-2xl">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Delete Account</h2>
                <button type="button" class="close la la-times" data-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-4">
                    I am very sad to see you go, but I understand. If you are sure this is what you want to do, please know the following:
                </p>
                <ul class="mb-4 ml-[20px] list-disc">
                    <li>All details about you will be deleted, including: Email and Password</li>
                    <li>All kingdoms will be given to the NPC who holds kingdoms.</li>
                    <li>All market listings will be deleted.</li>
                </ul>
                <p class="mb-4">
                    You will also receive one last email confirming that we have cleaned up your account.
                </p>
                <p class="mb-4">
                    Are you sure you want to do this? There is no going back, accept to create a new account.
                </p>
                <x-core.alerts.info-alert title="One second before you go ...">
                    Deleting your account to start over? Click Reset Account instead. You won't lose your account.
                </x-core.alerts.info-alert>

                <form action="{{route('delete.account', [
                    'user' => $user
                ])}}" id="character-deletion" method="POST">
                    @csrf

                </form>
            </div>
            <div class="modal-footer">
                <div class="flex ltr:ml-auto rtl:mr-auto">
                    <div class="mr-4">
                        <x-core.buttons.danger-button>Cancel</x-core.buttons.danger-button>
                    </div>
                    <x-core.buttons.primary-button onclick="event.preventDefault();
                           document.getElementById('character-deletion').submit();">Yes. I am Sure.</x-core.buttons.primary-button>
                </div>
            </div>
        </div>
    </div>
</div>
