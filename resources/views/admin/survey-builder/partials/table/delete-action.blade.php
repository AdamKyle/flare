@php
    $survey = \App\Flare\Models\Survey::where('title', $row['title'])->first();
@endphp

<form action="{{route('admin.surveys.delete', ['survey' => $survey->id])}}" method="post" class="mt-4">
    @csrf
    <x-core.buttons.danger-button type="submit">Delete</x-core.buttons.danger-button>
</form>
