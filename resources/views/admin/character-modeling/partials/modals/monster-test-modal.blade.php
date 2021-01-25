<div class="modal fade" id="monster-test-{{$monster->id}}" tabindex="-1" role="dialog" aria-labelledby="monster-test-label" aria-hidden="true">
    <div class="modal-dialog large-modal" role="document">
        <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="monster-test-label">Test Battle With: {{$monster->name}}</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="modal-body">
            @include('admin.monsters.partials.details', [
                'monster' => $monster,
                'canEdit' => false,
            ])

            <h5 class="mb-2 mt-3">Test Monster</h5>
            <hr />
            <div class="alert alert-info pb-2">
                <p>When the test is complete, You'll receieve an email. You will be able to come back to this page,
                and see a new button beside the monster called: View Data.<p>
            </div>

            @include('admin.character-modeling.partials.modals.partials.form', [
                'route'      => route('admin.character.modeling.test'),
                'users'      => $users,
                'model'      => $monster,
                'type'       => 'monster',
            ])
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-danger" data-dismiss="modal">Cancel</button>
        </div>
        </div>
    </div>
</div>