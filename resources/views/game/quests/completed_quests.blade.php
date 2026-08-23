@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.page.title
            title="Completed Quests"
            route="{{ route('game') }}"
            color="primary"
            link="Game"
        ></x-core.page.title>

        <x-core.tables.data-table
            :paginator="$paginator"
            :columns="$columns"
            :searchable="true"
            empty-message="No completed quests yet."
        />
    </x-core.layout.info-container>
@endsection
