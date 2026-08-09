@extends('layouts.app')

@section('content')
  <div class="w-full mx-auto md:w-2/3">
    <div class="p-4">
      <h1 class="text-2xl font-bold">{{ $skill->passiveSkill->name }}</h1>
      <p class="mt-2">{{ $skill->passiveSkill->description }}</p>
      <p class="mt-4">Current Level: {{ $skill->current_level }} / {{ $skill->passiveSkill->max_level }}</p>
      <p class="mt-2">Character: {{ $character->name }}</p>
    </div>
  </div>
@endsection
