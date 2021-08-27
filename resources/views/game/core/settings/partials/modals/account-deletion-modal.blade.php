<div class="modal" id="account-deletion" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Are you sure?</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>
                    I am very sad to see you go, but I understand. If you are sure this is what you want to do, please know the following:
                </p>
                <ul>
                    <li>All details about you will be deleted, including: Email and Password</li>
                    <li>All kingdoms will be given to the NPC who holds kingdoms.</li>
                    <li>All market listings will be deleted.</li>
                </ul>
                <p>
                    You will also receive one last email confirming that we have cleaned up your account.
                </p>
                <p>
                    Are you sure you want to do this? There is no going back, accept to create a new account.
                </p>
                <div class="alert alert-info">
                    <p>Deleting your account to start over? That's ok too. Just please wait for the email of confirmation to come through first.</p>
                    <p><strong>Remember</strong>: One account per player.</p>
                </div>
                <form action="{{route('delete.account', [
                    'user' => $user
                ])}}" id="character-deletion" method="POST">
                    @csrf
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-dismiss="modal">Cancel</button>
                <a class="btn btn-success"
                   onclick="event.preventDefault();
                       document.getElementById('character-deletion').submit();">
                    {{ __('Yes I am sure.') }}
                </a>
            </div>
        </div>
    </div>
</div>
