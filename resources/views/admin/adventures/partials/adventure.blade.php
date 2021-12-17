
<div class="w-full md:w-3/4 m-auto">
    <x-core.page-title
        title="{{$adventure->name}}"
        route="{{url()->previous()}}"
        color="primary"
        link="Back"
    />
</div>
@include('admin.adventures.partials.adventure-base', [
    'adventure' => $adventure
])
