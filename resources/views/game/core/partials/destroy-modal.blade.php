<div class="modal" id="slot-{{$slot->id}}" tabindex="-1" role="dialog" aria-labelledby="DestroyLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Destroy</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to do this? This action cannot be undone. You will lose the item.</p>


                <form id="destroy-item-{{$slot->id}}" action="{{route('game.destroy.item', ['character' => $character])}}" method="POST">
                    @csrf

                    <input type="hidden" name="slot_id" value="{{$slot->id}}" />
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
                <a class="btn btn-danger" href="{{route('game.destroy.item', ['character' => $character])}}"
                   onclick="event.preventDefault();
                                 document.getElementById('destroy-item-{{$slot->id}}').submit();">
                    {{ __('Destroy') }}
                </a>
            </div>
        </div>
    </div>
</div>
