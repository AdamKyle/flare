@extends('layouts.admin')

@section('content')
    <x-core.layout.info-container>
        <div id="guide-quest-editor" data-guide-quest-id="{{ is_null($guideQuest) ? 0 : $guideQuest->id }}"></div>
    </x-core.layout.info-container>
@endsection
