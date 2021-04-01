<div class="modal fade" id="adventure-test-{{$adventure->id}}" tabindex="-1" role="dialog" aria-labelledby="adventure-test-label" aria-hidden="true">
    <div class="modal-dialog large-modal" role="document">
        <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="adventure-test-label">Test Adventure: {{$adventure->name}}</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="modal-body">
            @include('admin.adventures.partials.adventure-base', [
                'adventure' => $adventure,
            ])

            <h5 class="mb-2 mt-3">Test Adventure</h5>
            <hr />
            <div class="alert alert-info pb-2">
                <p>
                    When the adventure test is over you will be able to 
                    come back here and see the results of that adventure.
                <p>
                <p>
                    Adventures can only be done once. This is because adventures may have multiple levels
                    and take a long time to run.
                </p>
            </div>
            @include('admin.character-modeling.partials.modals.partials.form', [
                'route'      => route('admin.character.modeling.test'),
                'users'      => $users,
                'model'      => $adventure,
                'type'       => 'adventure',
            ])
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-danger" data-dismiss="modal">Cancel</button>
        </div>
        </div>
    </div>
</div>