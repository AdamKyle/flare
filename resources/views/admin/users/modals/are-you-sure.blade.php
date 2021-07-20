<div class="modal" id="are-you-sure-"{{$character->user->id}} tabindex="-1" role="dialog" aria-labelledby="are-you-sure-"{{$character->user->id}} aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLabel">Are you sure?</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="modal-body">
            You can still unban the user at a later date. This will let them know that your choice is final though.
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-danger" data-dismiss="modal">Cancel</button>
            <x-forms.button-with-form
                form-route="{{route('user.ignore.unban.request', [
                    'user' => $character->user->id
                ])}}"
                form-id="{{$character->user->id}}-character-user"
                button-title="Continue"
                class="btn btn-primary"
            />
        </div>
        </div>
    </div>
</div>
