@php
    $suggestion = \App\Flare\Models\SuggestionAndBugs::where('title', $row['title'])->first();
@endphp

<form action="{{route('admin.feedback.delete', ['feedbackId' => $suggestion->id])}}" method="post" class="mt-4">
    @csrf
    <x-core.buttons.danger-button type="submit">Delete</x-core.buttons.danger-button>
</form>
