<div class="modal" id="skill-train-{{$skill->id}}" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLabel">Train {{$skill->name}}?</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <p>
              You can only train one skill at a time.
              If you would like to procede, please choose how much xp per fight is awarded to the training of the skill.
          </p>
          <div class="alert alert-warning">
              <strong>Please note</strong> if you select 100%, you will not gain any levels as all your exp is going towards skills.
          </div>
          <form action="{{route('train.skill', [
            'character' => $character->id
          ])}}" id="train-skill-{{$skill->id}}" method="POST">
              @csrf

              <input type="hidden" name="skill_id" value="{{$skill->id}}" />

              <div class="form-group">
                <label for="xp_percentage">Example select</label>
                <select class="form-control" id="xp_percentage" name="xp_percentage">
                  <option value="0.10" {{$skill->xp_towards === 0.10 ? 'selected' : ''}}>10%</option>
                  <option value="0.20" {{$skill->xp_towards === 0.20 ? 'selected' : ''}}>20%</option>
                  <option value="0.30" {{$skill->xp_towards === 0.30 ? 'selected' : ''}}>30%</option>
                  <option value="0.40" {{$skill->xp_towards === 0.40 ? 'selected' : ''}}>40%</option>
                  <option value="0.50" {{$skill->xp_towards === 0.50 ? 'selected' : ''}}>50%</option>
                  <option value="0.60" {{$skill->xp_towards === 0.60 ? 'selected' : ''}}>60%</option>
                  <option value="0.70" {{$skill->xp_towards === 0.70 ? 'selected' : ''}}>70%</option>
                  <option value="0.80" {{$skill->xp_towards === 0.80 ? 'selected' : ''}}>80%</option>
                  <option value="0.90" {{$skill->xp_towards === 0.90 ? 'selected' : ''}}>90%</option>
                  <option value="1" {{$skill->xp_towards === 1.0 ? 'selected' : ''}}>100%</option>
                </select>
              </div>
          </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            <a class="btn btn-primary" href="{{route('train.skill', [
              'character' => $character->id
            ])}}"
                onclick="event.preventDefault();
                                document.getElementById('train-skill-{{$skill->id}}').submit();">
                {{ __('Train this skill') }}
            </a>
        </div>
      </div>
    </div>
  </div>
