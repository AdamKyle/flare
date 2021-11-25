@extends('layouts.information')

@section('content')
    <div class="tw-w-full lg:tw-w-3/5 tw-m-auto tw-mt-20 tw-mb-10">
        @include('admin.monsters.partials.monster', [
            'monster' => $monster,
        ])
    </div>
@endsection
