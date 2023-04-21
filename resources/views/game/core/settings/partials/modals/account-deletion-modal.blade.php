<x-core.modals.modal id="accountDeletion" title="Delete Account" formId="character-deletion" formActionTitle="Yes. I am sure">
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
</x-core.modals.modal>
