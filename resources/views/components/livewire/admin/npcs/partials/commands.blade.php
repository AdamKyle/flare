<div>
    <div class="alert alert-info mb-2 mt-2">
        <h5>How this works</h5>
        <p>
            You will enter a command, something like "Give Kingdom", for example,
            then you select a command type that best matches that command.
        </p>
        <p>
            Players can then type: <pre>/m npcName: Your Command</pre> to activate that command.
        </p>
        <p>You can assign one command here and then view the NPC to add more commands later.</p>
        <p>Commands must be unique.</p>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="command-name">Command: </label>
                <input type="text" class="form-control" name="command-name" value="" wire:model="npcCommand.command">
                @error('npc.command') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="npc-type">Command Type: </label>
                <select wire:model="npcCommand.command_type" class="form-control">
                    <option>Please select</option>
                    @foreach($commandTypes as $key => $type)
                        <option value="{{$key}}">{{$type}}</option>
                    @endforeach
                </select>
                @error('npcCommand.command_type') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
</div>
