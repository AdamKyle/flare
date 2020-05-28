<div class="modal fade" id="skill-train-{{$skill->id}}" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
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
              If you would like to procede, plese choose how much xp per fight is awarded to the training of the skill.
          </p>
          <p>
              Please note, if you select 100%, you will not gain any levels as all your exp is going towards skills. 
              Skills also gain levels randomly. The more xp the more chance to gain a skill level. 
              There are also items that speed up the process of skill leveling.
          </p>

          <p>
            Placing 100% of xp towards a skill does not automatically mean you will gain a skill level every fight.
          </p>
          <form action="{{route('train.skill')}}" id="train-skill-{{$skill->id}}" method="POST">
              @csrf

              <input type="hidden" name="skill_id" value="{{$skill->id}}" />

              <div class="form-group">
                <label for="xp_percentage">Example select</label>
                <select class="form-control" id="xp_percentage" name="xp_percentage">
                  <option value="0.10">10%</option>
                  <option value="0.20">20%</option>
                  <option value="0.30">30%</option>
                  <option value="0.40">40%</option>
                  <option value="0.50">50%</option>
                  <option value="0.60">60%</option>
                  <option value="0.70">70%</option>
                  <option value="0.80">80%</option>
                  <option value="0.90">90%</option>
                  <option value="1">100%</option>
                </select>
              </div>
          </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            <a class="btn btn-primary" href="{{route('train.skill')}}"
                onclick="event.preventDefault();
                                document.getElementById('train-skill-{{$skill->id}}').submit();">
                {{ __('Train this skill') }}
            </a>
        </div>
      </div>
    </div>
  </div>