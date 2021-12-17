@extends('layouts.app')

@section('content')
  <div class="mt-10 mb-10 w-full lg:w-3/5 m-auto">
    <x-core.page-title
      title="{{is_null($skill) ? 'Create New Passive Skill' : 'Edit: ' . $skill->name}}"
      route="{{route('passive.skills.list')}}"
      color="primary" link="Back"
    >
    </x-core.page-title>
    <hr />
    <x-core.cards.card>
      <form action="{{is_null($skill) ? route('passive.skill.store') : route('passive.skill.update', ['passiveSkill' => $skill->id])}}" method="POST">
        @csrf()
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label for="passive_skill_name">Name: </label>
              <input type="text" class="form-control required" id="passive_skill_name" name="name" value="{{!is_null($skill) ? $skill->name : ''}}">
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label for="passive_skill_description">Description: </label>
              <textarea class="form-control required" id="passive_skill_description" name="description">{{!is_null($skill) ? $skill->description : ''}}</textarea>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-4">
            <div class="form-group">
              <label for="passive_skill_max_level">Max Level: </label>
              <input type="number" class="form-control required" id="passive_skill_max_level" name="max_level" value="{{!is_null($skill) ? $skill->max_level : ''}}">
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group">
              <label for="passive_skill_effect_type">Effects: </label>
              <select class="custom-select form-control required" id="passive_skill_effect_type" name="effect_type">
                <option value="">Please Select</option>
                @foreach($effects as $id => $name)
                  <option {{!is_null($skill) ? ($skill->effect_type === $id ? 'selected' : '') : ''}} value="{{$id}}">{{$name}}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group">
              <label for="passive_skill_bonus_per_level">Bonus Per Level (%): </label>
              <input type="number" step="0.01" class="form-control required" id="passive_skill_bonus_per_level" name="bonus_per_level" value="{{!is_null($skill) ? $skill->bonus_per_level : ''}}">
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-4">
            <div class="form-group">
              <label for="passive_skill_parent_skill_id">Belongs To Skill: </label>
              <select class="custom-select form-control required" id="passive_skill_parent_skill_id" name="parent_skill_id">
                <option value="">Please Select</option>
                @foreach($parentSkills as $id => $name)
                  <option {{!is_null($skill) ? ($skill->parent_skill_id === $id ? 'selected' : '') : ''}} value="{{$id}}">{{$name}}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group">
              <label for="passive_skill_unlocks_at_level">Unlocks at level: </label>
              <input type="number" class="form-control required" id="passive_skill_unlocks_at_level" name="unlocks_at_level" value="{{!is_null($skill) ? $skill->unlocks_at_level : ''}}">
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group">
              <label for="passive_skill_hours_per_level">Hours per level: </label>
              <input type="number" class="form-control required" id="passive_skill_hours_per_level" name="hours_per_level" value="{{!is_null($skill) ? $skill->hours_per_level : ''}}">
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-md-6">
            <div class="form-group form-check">
              <input type="checkbox" class="form-check-input" id="passive_skill_is_locked" name="is_locked" {{!is_null($skill) ? $skill->is_locked ? 'checked' : '' : ''}}>
              <label class="form-check-label" for="passive_skill_is_locked">Is locked?</label>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group form-check">
              <input type="checkbox" class="form-check-input" id="passive_skill_is_parent" name="is_parent" {{!is_null($skill) ? $skill->is_parent ? 'checked' : '' : ''}}>
              <label class="form-check-label" for="passive_skill_is_parent">Is parent?</label>
            </div>
          </div>
        </div>
        @if (is_null($skill))
          <button type="submit" class="btn btn-primary">Create Passive Skill</button>
        @else
          <button type="submit" class="btn btn-success">Update Passive Skill</button>
        @endif
      </form>
    </x-core.cards.card>
  </div>
@endsection