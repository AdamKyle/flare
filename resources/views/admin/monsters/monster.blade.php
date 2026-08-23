@extends('layouts.admin')

@section('content')
    <div class="mx-auto w-full px-4 md:w-2/3">
        @include('admin.monsters.partials.monster', ['monster' => $monster, 'quest' => $quest])
    </div>
@endsection
