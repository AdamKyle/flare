<a class="btn btn-danger btn-sm" href="{{ route('admin.character.modeling.reset-inventory', ['character' => $character]) }}"
    onclick="event.preventDefault();
            document.getElementById('reset-inventory').submit();">
    Reset Inventory
</a>

<form id="reset-inventory" action="{{ route('admin.character.modeling.reset-inventory', ['character' => $character]) }}" method="POST" style="display: none;">
    @csrf
</form>