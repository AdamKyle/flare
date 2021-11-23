
<div class="tw-w-full md:tw-w-3/4 tw-m-auto">
    <x-core.page-title
        title="{{$adventure->name}}"
        route="{{url()->previous()}}"
        color="success"
        link="Back"
    />
</div>
@include('admin.adventures.partials.adventure-base', [
    'adventure' => $adventure
])
