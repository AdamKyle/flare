<div class="modal" id="slot-use-{{$slot->id}}" tabindex="-1" role="dialog" aria-labelledby="UseLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Use</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                @include('game.items.partials.item-usable-section', [
                    'item'   => $slot->item,
                    'skills' => $slot->affects_skills,
                ])
                <form id="use-item-{{$slot->id}}" action="{{route('game.item.use', ['character' => $character, 'item' => $slot->item])}}" method="POST">
                    @csrf
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
                <a class="btn btn-success" href="{{route('game.item.use', ['character' => $character, 'item' => $slot->item])}}"
                   onclick="event.preventDefault();
                                 document.getElementById('use-item-{{$slot->id}}').submit();">
                    {{ __('Use') }}
                </a>
            </div>
        </div>
    </div>
</div>
