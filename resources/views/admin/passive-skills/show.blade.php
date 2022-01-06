@extends('layouts.app')

@section('content')
  <div class="tw-mt-10 tw-mb-10 tw-w-full lg:tw-w-3/5 tw-m-auto">
    <x-core.page-title
      title="{{$skill->name}}"
      route="{{route('passive.skills.list')}}"
      color="primary" link="Back"
    >
    </x-core.page-title>
    @include('admin.passive-skills.partials.show', ['skill' => $skill])
  </div>
@endsection