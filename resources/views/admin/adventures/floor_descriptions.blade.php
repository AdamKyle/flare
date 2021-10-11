@extends('layouts.app')

@section('content')
  @dump(old())
  <x-core.cards.card-with-title
    title="Floor Descriptions"
  >
    <form action="{{route('post.adventure.floor_descriptions', ['adventure' => $adventure->id])}}" method="POST">
      @csrf
      @php
        $descriptions = $adventure->floorDescriptions->pluck('description')->toArray();
      @endphp

      @for ($i = 1; $i < $adventure->levels; $i++)
        @php
          $index = $i - 1;

          $oldValue = old('level-' . $i);
          $value    = '';

          if (!is_null($oldValue)) {
              $value = $oldValue;
          } else if (isset($descriptions[$index])){
              $value = $descriptions[$index];
          }
        @endphp
        <div class="form-group">
          <label for="{{'level-' . $i}}">Level {{$i}} Description</label>

          <textarea class="form-control" id="{{'level-' . $i}}" rows="6" name="{{'level-' . $i}}">{{$value}}</textarea>
        </div>
      @endfor
      <hr />
      <button type="submit" class="btn btn-primary">Save Floor Descriptions</button>
    </form>
  </x-core.cards.card-with-title>
@endsection