<div class="modal" id="slot-use-{{$slot->id}}" tabindex="-1" role="dialog" aria-labelledby="UseLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">{{$slot->item->name}}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info mb-3 mt-2">
                    <p>
                        When using this item, it's effects will be applied for a specified amount of time in minutes. Usable items, accept those that are used
                        to attack kingdoms, are used from the character inventory section. These items can only be crafted and <strong>can be sold on the market</strong>.
                    </p>
                    <p>
                        Upon being used, you can see the "applied boons" under the Active Boons tab on the character sheet where you can see the boon
                        and even cancel its effects early if you so desire.
                    </p>
                    <p>
                        These items can crafted via

                        @if (isset($skill))
                            @if (auth()->user()->hasRole('Admin'))
                                <a href="{{route('skills.skill', ['skill' => $skill->id])}}">Alchemy</a>.
                            @elseif (auth()->user())
                                <a href="{{route('skill.character.info', ['skill' => $skill->id])}}">Alchemy</a>.
                            @endif
                        @endif
                    </p>
                </div>

                @if ($slot->item->damages_kingdoms)
                    <div class="alert alert-warning mb-3">
                        </p>
                        This is a single use item that can only be used when attacking kingdoms. The way this works is you move to a kingdom you want to attack.
                        Then you click <strong>Attack Kingdom</strong>. from here, instead of selecting a kingdom to attack with, you can select to use an item, from there you can pick
                        the item to use.
                        <p>

                        <p>
                            Upon use, the damage shown below will be whats done to all aspects of the kingdom. Buildings, morale, units - all reduced - at once.
                        </p>

                        <p>
                            You cannot use these items to take a kingdom. Even if you decimate the kingdom, you must move in with a <strong>settler</strong> to take the kingdom.
                        </p>
                    </div>
                    <dl>
                        <dt>Damages Kingdom For:</dt>
                        <dd>{{$slot->item->kingdom_damage * 100}}%</dd>
                    </dl>
                @else
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
                @endif
                <form id="use-item-{{$slot->id}}" action="{{route('game.item.use', ['character' => $character, 'item' => $slot->item])}}" method="POST">
                    @csrf
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
                <a class="btn btn-success" href="{{route('game.item.use', ['character' => $character, 'item' => $slot->item])}}"
                   onclick="event.preventDefault();
                                 document.getElementById('use-item-{{$slot->id}}').submit();">
                    {{ __('Use') }}
                </a>
            </div>
        </div>
    </div>
</div>
