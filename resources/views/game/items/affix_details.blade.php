<x-core.modals.modal
  id="affix-details-{{$itemAffix->id}}"
  title="{{$itemAffix->name}}"
  largeModal="true"
>
  <p class="mt-4 mb-4">{{ $itemAffix->description }}</p>
  <div class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"></div>

  <div
    class="{{ $itemAffix->damage !== 0 || $itemAffix->reduces_enemy_stats || ! is_null($itemAffix->steal_life_amount) || $itemAffix->entranced_chance > 0 || $itemAffix->devouring_light > 0 || $itemAffix->resistance_reduction > 0 ? 'grid max-h-[350px] gap-2 overflow-y-auto md:grid-cols-2' : ' max-h-[350px] overflow-y-auto' }}"
  >
    <div>
      <div class="grid gap-2 md:grid-cols-2">
        <div>
          <h3 class="text-sky-600 dark:text-sky-500">Stat Modifiers</h3>
          <x-core.separator.separator />
          <dl>
            <dt>Str Modifier:</dt>
            <dd>{{ $itemAffix->str_mod * 100 }}%</dd>
            <dt>Dex Modifier:</dt>
            <dd>{{ $itemAffix->dex_mod * 100 }}%</dd>
            <dt>Dur Modifier:</dt>
            <dd>{{ $itemAffix->dur_mod * 100 }}%</dd>
            <dt>Int Modifier:</dt>
            <dd>{{ $itemAffix->int_mod * 100 }}%</dd>
            <dt>Chr Modifier:</dt>
            <dd>{{ $itemAffix->chr_mod * 100 }}%</dd>
            <dt>Agi Modifier:</dt>
            <dd>{{ $itemAffix->agi_mod * 100 }}%</dd>
            <dt>Focus Modifier:</dt>
            <dd>{{ $itemAffix->focus_mod * 100 }}%</dd>
          </dl>
        </div>
        <div
          class="my-3 block border-b-2 border-b-gray-300 md:hidden dark:border-b-gray-600"
        ></div>
        <div>
          <h3 class="text-sky-600 dark:text-sky-500">
            Damage/AC/Healing Modifiers
          </h3>
          <x-core.separator.separator />
          <dl>
            <dt>Base Attack Modifier:</dt>
            <dd>{{ $itemAffix->base_damage_mod * 100 }}%</dd>
            <dt>Base AC Modifier:</dt>
            <dd>{{ $itemAffix->base_ac_mod * 100 }}%</dd>
            <dt>Base Healing Modifier:</dt>
            <dd>{{ $itemAffix->base_healing_mod * 100 }}%</dd>
          </dl>
          <x-core.separator.separator />
          <h3 class="text-sky-600 dark:text-sky-500">Class Modifier</h3>
          <x-core.separator.separator />
          <dl>
            <dt>Class Bonus Mod:</dt>
            <dd>{{ $itemAffix->class_bonus * 100 }}%</dd>
          </dl>
        </div>
      </div>
      <div
        class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"
      ></div>
      <div class="grid gap-2 md:grid-cols-2">
        <div>
          <h3 class="text-sky-600 dark:text-sky-500">Skill Modifiers</h3>
          <x-core.separator.separator />
          <dl>
            <dt>Skill Name:</dt>
            <dd>
              {{ is_null($itemAffix->skill_name) ? 'N/A' : $itemAffix->skill_name }}
            </dd>
            <dt>Skill XP Bonus (When Training):</dt>
            <dd>
              {{ is_null($itemAffix->skill_name) ? 0 : $itemAffix->skill_training_bonus * 100 }}%
            </dd>
            <dt>Skill Bonus (When using)</dt>
            <dd>
              {{ is_null($itemAffix->skill_bonus) ? 0 : $itemAffix->skill_bonus * 100 }}%
            </dd>
          </dl>
        </div>
        <div
          class="my-3 block border-b-2 border-b-gray-300 md:hidden dark:border-b-gray-600"
        ></div>
        <div>
          <h3 class="text-sky-600 dark:text-sky-500">Other Skill Modifiers</h3>
          <x-core.separator.separator />
          <dl>
            <dt>Damage Modifier:</dt>
            <dd>{{ $itemAffix->base_damage_mod_bonus * 100 }}%</dd>
            <dt>AC Modifier:</dt>
            <dd>{{ $itemAffix->base_ac_mod_bonus * 100 }}%</dd>
            <dt>Healing Modifier:</dt>
            <dd>{{ $itemAffix->base_healing_mod_bonus * 100 }}%</dd>
            <dt>Fight Timeout Modifier:</dt>
            <dd>{{ $itemAffix->fight_time_out_mod_bonus * 100 }}%</dd>
            <dt>Move Timeout Modifier:</dt>
            <dd>{{ $itemAffix->move_time_out_mod_bonus * 100 }}%</dd>
          </dl>
        </div>
      </div>
      <div
        class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"
      ></div>
      <div>
        <h3 class="text-sky-600 dark:text-sky-500">Enchanting Information</h3>
        <div
          class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"
        ></div>
        <dl>
          <dt>Base Cost:</dt>
          <dd>{{ number_format($itemAffix->cost) }} Gold</dd>
          <dt>Intelligence Required:</dt>
          <dd>{{ number_format($itemAffix->int_required) }}</dd>
          <dt>Level Required:</dt>
          <dd>{{ $itemAffix->skill_level_required }}</dd>
          <dt>Level Trivial:</dt>
          <dd>{{ $itemAffix->skill_level_trivial }}</dd>
        </dl>
      </div>
    </div>

    <div>
      @if ($itemAffix->damage !== 0)
        <h3 class="text-sky-600 dark:text-sky-500">Damage Information</h3>
        <div
          class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"
        ></div>
        <dl>
          <dt>Damage:</dt>
          <dd>{{ number_format($itemAffix->damage) }}</dd>
          <dt>Is Damage Irresistible?:</dt>
          <dd>
            {{ $itemAffix->irresistible_damage ? 'Yes' : 'No' }}
          </dd>
          <dt>Can Stack:</dt>
          <dd>{{ $itemAffix->damage_can_stack ? 'Yes' : 'No' }}</dd>
        </dl>
        <div
          class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"
        ></div>
      @endif

      @if ($itemAffix->reduces_enemy_stats)
        <h3 class="text-sky-600 dark:text-sky-500">Enemy Stat Reduction</h3>
        <div
          class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"
        ></div>
        <dl>
          <dt>Str Reduction:</dt>
          <dd>{{ $itemAffix->str_reduction * 100 }}%</dd>
          <dt>Dex Reduction:</dt>
          <dd>{{ $itemAffix->dex_reduction * 100 }}%</dd>
          <dt>Dur Reduction:</dt>
          <dd>{{ $itemAffix->dur_reduction * 100 }}%</dd>
          <dt>Int Reduction:</dt>
          <dd>{{ $itemAffix->int_reduction * 100 }}%</dd>
          <dt>Chr Reduction:</dt>
          <dd>{{ $itemAffix->chr_reduction * 100 }}%</dd>
          <dt>Agi Reduction:</dt>
          <dd>{{ $itemAffix->agi_reduction * 100 }}%</dd>
          <dt>Focus Reduction:</dt>
          <dd>{{ $itemAffix->focus_reduction * 100 }}%</dd>
        </dl>
        <div
          class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"
        ></div>
      @endif

      @if (! is_null($itemAffix->steal_life_amount))
        <h3 class="text-sky-600 dark:text-sky-500">Life Stealing</h3>
        <div
          class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"
        ></div>
        <dl>
          <dt>Steal Life Amount:</dt>
          <dd>{{ $itemAffix->steal_life_amount * 100 }}%</dd>
        </dl>
        <div
          class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"
        ></div>
      @endif

      @if ($itemAffix->entranced_chance > 0)
        <h3 class="text-sky-600 dark:text-sky-500">Entrance Chance</h3>
        <div
          class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"
        ></div>
        <dl>
          <dt>Entrance Chance:</dt>
          <dd>{{ $itemAffix->entranced_chance * 100 }}%</dd>
        </dl>
        <div
          class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"
        ></div>
      @endif

      @if ($itemAffix->devouring_light > 0)
        <h3 class="text-sky-600 dark:text-sky-500">Devouring Light</h3>
        <div
          class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"
        ></div>
        <dl>
          <dt>Devouring Light Chance:</dt>
          <dd>{{ $itemAffix->devouring_light * 100 }}%</dd>
        </dl>
        <div
          class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"
        ></div>
      @endif

      @if ($itemAffix->skill_reduction > 0)
        <h3 class="text-sky-600 dark:text-sky-500">Enemy Skill Reduction</h3>
        <div
          class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"
        ></div>
        <dl>
          <dt>Skills Affected:</dt>
          <dd>Accuracy, Dodge, Casting Accuracy and Criticality</dd>
          <dt>Skills Reduced By:</dt>
          <dd>{{ $itemAffix->skill_reduction * 100 }}%</dd>
        </dl>
        <div
          class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"
        ></div>
      @endif

      @if ($itemAffix->resistance_reduction > 0)
        <h3 class="text-sky-600 dark:text-sky-500">
          Enemy Resistance Reduction
        </h3>
        <div
          class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"
        ></div>
        <dl>
          <dt>Resistance Reduction:</dt>
          <dd>{{ $itemAffix->resistance_reduction * 100 }}%</dd>
        </dl>
        <div
          class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"
        ></div>
      @endif
    </div>
  </div>
</x-core.modals.modal>
