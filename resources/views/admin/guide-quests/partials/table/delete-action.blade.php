@php
    $guideQuest = \App\Flare\Models\GuideQuest::where('name', $row['name'])->first();
@endphp

<form action="{{route('admin.guide-quests.delete', ['guideQuest' => $guideQuest->id])}}" method="post" class="mt-4">
    @csrf
    <x-core.buttons.danger-button type="submit">Delete</x-core.buttons.danger-button>
</form>
