<div class="modal" id="use-multiple-items" tabindex="-1" role="dialog" aria-labelledby="UseLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Using Multiple Items</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div wire:loading.class.remove="hide" class="alert alert-info hide mt-2 mb-3">
                    <i class="fas fa-spinner fa-spin"></i> Applying items. <strong>Do not</strong> refresh the page. The page will refresh when done.
                    It is advised you do not do any additional actions while this is processing as it can slow the game down.
                </div>
                @php
                    $slots = $character->inventory->slots()->findMany($selected);
                @endphp

                @foreach($slots as $index => $slot)
                    <h3 class="{{$index > 0 ? 'mt-4' : 'mt-2'}}">{{$slot->item->name}}</h3>
                    <hr />
                    <dl>
                        <dt>Lasts For: </dt>
                        <dd>{{$slot->item->lasts_for}} Minutes</dd>

                        @if ($slot->item->stat_increase)
                            <dt>Increases all core stats by: </dt>
                            <dd>{{$slot->item->increase_stat_by * 100}}%</dd>
                        @endif
                        @if (!is_null($slot->item->affects_skill_type))
                            <dt>Skills Affected: </dt>
                            <dd>{{empty($skills) ? 'None' : implode(', ', $skills)}}</dd>
                            <dt>Skill Bonus: </dt>
                            <dd>{{$slot->item->increase_skill_bonus_by * 100}}%</dd>
                            <dt>Skill Training Bonus: </dt>
                            <dd>{{$slot->item->increase_skill_training_bonus_by * 100}}%</dd>
                            <dt>Base Damage Mod Bonus:</dt>
                            <dd>{{$slot->item->base_damage_mod_bonus * 100}}%</dd>
                            <dt>Base Healing Mod Bonus:</dt>
                            <dd>{{$slot->item->base_healing_mod_bonus * 100}}%</dd>
                            <dt>Base AC Mod Bonus:</dt>
                            <dd>{{$slot->item->base_ac_mod_bonus * 100}}%</dd>
                            <dt>Fight Timeout Mod Bonus:</dt>
                            <dd>{{$slot->item->fight_time_out_mod_bonus * 100}}%</dd>
                            <dt>Move Timeout Mod Bonus:</dt>
                            <dd>{{$slot->item->move_time_out_mod_bonus * 100}}%</dd>
                        @endif
                    </dl>
                    <hr />
                @endforeach
                <div wire:loading.class.remove="hide" class="alert alert-info hide mt-2 mb-3">
                    <i class="fas fa-spinner fa-spin"></i> Applying items. <strong>Do not</strong> refresh the page. The page will refresh when done.
                    It is advised you do not do any additional actions while this is processing as it can slow the game down.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
                <a class="btn btn-success" wire:click="useAllSelectedItems">Use Selected</a>
            </div>
        </div>
    </div>
</div>
