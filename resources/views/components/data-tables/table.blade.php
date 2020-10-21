<table class="table table-bordered data-table">
    {{$slot}}
</table>

<div class="row">
    <div class="col-md-6 text-left text-muted">
        Showing {{ $collection->firstItem() }} to {{ $collection->lastItem() }} out of {{ $collection->total() }} results
    </div>

    <div class="col-md-6">
        {{ $collection->links() }}
    </div>
</div>
