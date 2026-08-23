@extends('layouts.app')

@section('content')
    <x-core.page.title
        title="Map Gems"
        route="{{ route('info.page', ['pageName' => 'home']) }}"
        color="success"
        link="Information"
    />

    <x-core.tables.data-table
        :paginator="$paginator"
        :columns="$columns"
        :searchable="true"
        empty-message="No map gems found."
    />
@endsection
