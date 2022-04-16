@extends('layouts.app')

@section('content')
    <div class="lg:w-3/4 lg:px-4 pt-5 lg:pt-0 m-auto">
        <h3>
            @if (!is_null($item))
                Edit {{$item->name}}
            @else
                Create New Item
            @endif
        </h3>
        <div class="card mt-5 p-5">
            <div class="tabs wizard wizard-style-2">
                <nav class="tab-nav">
                    <button class="nav-link h5 active" data-toggle="tab" data-target="#tab-style-2-1">
                        Basic Info
                        <small>Basic information about the item.</small>
                    </button>
                    <button class="nav-link h5" data-toggle="tab" data-target="#tab-style-2-2">
                        Stat Data
                        <small>Set up the stat data for the item</small>
                    </button>
                    <button class="nav-link h5" data-toggle="tab" data-target="#tab-style-2-3">
                        Other Modifiers
                        <small>Misc. modifiers that effect the character</small>
                    </button>
                    <button class="nav-link h5" data-toggle="tab" data-target="#tab-style-2-4">
                        Crafting Details
                        <small>Crafting Details</small>
                    </button>
                    <button class="nav-link h5" data-toggle="tab" data-target="#tab-style-2-5">
                        Usable Details
                        <small>When the item is usable</small>
                    </button>
                </nav>
                <div class="tab-content mt-8">
                    <div id="tab-style-2-1" class="collapse open">
                        <div class="mb-5">
                            <label class="label block mb-2" for="name">Name</label>
                            <input id="name" type="text" class="form-control" name="name" value="{{!is_null($item) ? $item->name : ''}}">
                        </div>
                        <div class="mb-5">
                            <label class="label block mb-2" for="type">Type</label>
                            <input id="type" type="text" class="form-control" name="type" value="{{!is_null($item) ? $item->type : ''}}">
                        </div>
                        <div class="mb-5">
                            <label class="label block mb-2" for="description">Description</label>
                            <textarea id="description" class="form-control" name="description" >
                                {{!is_null($item) ? trim($item->description) : ''}}
                            </textarea>
                        </div>
                        <div class="mb-5">
                            <label class="label block mb-2" for="default_position">Default Position: </label>
                            <select class="form-control" name="default_position" {{!is_null($item) ? in_array($item->type, $defaultPositions) ? '' : 'disabled' : ''}}>
                                <option value="">Please select</option>
                                @foreach($defaultPositions as $defaultPosition)
                                    <option value={{$defaultPosition}} {{!is_null($item) ? $item->default_position === $defaultPosition ? 'selected' : '' : ''}}>{{$defaultPosition}}</option>
                                @endforeach
                            </select>
                            <span class="text-muted mt-2 text-xs text-gray-700 dark:text-gray-400">Only needed for armor based items where the player cannot select a position.</span>
                        </div>
                    </div>
                    <div id="tab-style-2-2" class="collapse">
                        <div class="grid md:grid-cols-2 md:gap-3">
                            <div>
                                <h3 class="mb-3">Base Info</h3>
                                <div class="mb-5">
                                    <label class="label block mb-2" for="base_damage">Base Damage</label>
                                    <input id="base_damage" type="number" class="form-control" name="base_damage" value="{{!is_null($item) ? $item->base_damage : ''}}">
                                </div>
                                <div class="mb-5">
                                    <label class="label block mb-2" for="base_ac">Base AC</label>
                                    <input id="base_ac" type="number" class="form-control" name="base_ac" value="{{!is_null($item) ? $item->base_ac : ''}}">
                                </div>
                                <div class="mb-5">
                                    <label class="label block mb-2" for="base_healing">Base Healing</label>
                                    <input id="base_healing" type="number" class="form-control" name="base_healing" value="{{!is_null($item) ? $item->base_healing : ''}}">
                                </div>
                                <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                                <label class="custom-checkbox mb-5" for="can_resurrect">
                                    <input type="checkbox" id="can_resurrect" name="can_resurrect" {{!is_null($item) ? $item->can_resurrect ? 'checked' : '' : ''}}>
                                    <span></span>
                                    <span>Can Resurrect?</span>
                                </label>
                                <div class="mb-5">
                                    <label class="label block mb-2" for="resurrection_chance">Resurrection Chance</label>
                                    <input id="resurrection_chance" type="number" class="form-control" name="resurrection_chance" value="{{!is_null($item) ? $item->resurrection_chance : ''}}">
                                </div>
                            </div>
                            <div class="mt-3 md:mt-0">
                                <h3 class="mb-3">Enemy Reductions</h3>
                                <div class="mb-5">
                                    <label class="label block mb-2" for="spell_evasion">Spell Evasion</label>
                                    <input id="spell_evasion" type="number" class="form-control" name="spell_evasion" value="{{!is_null($item) ? $item->spell_evasion : ''}}">
                                </div>
                                <div class="mb-5">
                                    <label class="label block mb-2" for="artifact_annulment">Artifact Annulment</label>
                                    <input id="artifact_annulment" type="number" class="form-control" name="artifact_annulment" value="{{!is_null($item) ? $item->artifact_annulment : ''}}">
                                </div>
                                <div class="mb-5">
                                    <label class="label block mb-2" for="affix_damage_reduction">Affix Damage Reduction</label>
                                    <input id="affix_damage_reduction" type="number" class="form-control" name="affix_damage_reduction" value="{{!is_null($item) ? $item->affix_damage_reduction : ''}}">
                                </div>
                                <div class="mb-5">
                                    <label class="label block mb-2" for="healing_reduction">Healing Reduction</label>
                                    <input id="healing_reduction" type="number" class="form-control" name="healing_reduction" value="{{!is_null($item) ? $item->healing_reduction : ''}}">
                                </div>
                            </div>
                        </div>
                        <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                        <h3 class="mb-3">Devouring Info</h3>
                        <div class="mb-5">
                            <label class="label block mb-2" for="devouring_light">Devouring Light Chance</label>
                            <input id="devouring_light" type="number" class="form-control" name="devouring_light" value="{{!is_null($item) ? $item->devouring_light : ''}}">
                        </div>
                        <div class="mb-5">
                            <label class="label block mb-2" for="devouring_darkness">Devouring Darkness</label>
                            <input id="devouring_darkness" type="number" class="form-control" name="devouring_darkness" value="{{!is_null($item) ? $item->devouring_darkness : ''}}">
                        </div>

                    </div>
                    <div id="tab-style-2-3" class="collapse">
                        Step 3 Content
                    </div>
                    <div id="tab-style-2-4" class="collapse">
                        Step 4 Content
                    </div>
                    <div id="tab-style-2-5" class="collapse">
                        Step 5 Content
                    </div>
                </div>
                <div class="mt-5">
                    <div class="btn-group">
                        <button type="button" class="hover:bg-blue-700 hover:drop-shadow-md dark:text-white hover:text-gray-300 bg-blue-600 dark:bg-blue-700 text-white dark:hover:text-white font-semibold
  py-2 px-4 rounded-sm drop-shadow-sm mr-2" data-toggle="wizard"
                                data-direction="previous">Previous</button>
                        <button type="button" class="hover:bg-blue-700 hover:drop-shadow-md dark:text-white hover:text-gray-300 bg-blue-600 dark:bg-blue-700 text-white dark:hover:text-white font-semibold
  py-2 px-4 rounded-sm drop-shadow-sm mr-2" data-toggle="wizard"
                                data-direction="next">Next</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
