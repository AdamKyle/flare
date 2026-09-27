import React, { ReactNode } from 'react';

import SkillFormFieldsProps from '../../types/skill-form-fields-props';
import { parseSkillDropdownValue } from '../../utils/parse-skill-dropdown-value';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import FieldWrapper from 'ui/forms/field-wrapper';
import NumberField from 'ui/forms/number-field';

const SkillTimerModifierFields = ({
  state,
  errors,
  form_options: formOptions,
  on_change: onChange,
}: SkillFormFieldsProps): ReactNode => {
  const classItems: DropdownItem[] = formOptions.classes.map((gameClass) => ({
    label: gameClass.name,
    value: gameClass.id,
  }));

  return (
    <div className="space-y-4">
      <div className="grid gap-4 md:grid-cols-2">
        <NumberField
          id="skill-class-bonus"
          label="Class Bonus % per level"
          value={state.class_bonus}
          on_change={(value) => onChange('class_bonus', value)}
          error={errors.class_bonus}
          description="A decimal fraction, e.g. 0.01 for 1%."
        />
        <NumberField
          id="skill-fight-timeout-mod"
          label="Fight Timeout Reduction % per level"
          value={state.fight_time_out_mod_bonus_per_level}
          on_change={(value) =>
            onChange('fight_time_out_mod_bonus_per_level', value)
          }
          error={errors.fight_time_out_mod_bonus_per_level}
          description="A decimal fraction, e.g. 0.01 for 1%."
        />
        <NumberField
          id="skill-move-timeout-mod"
          label="Move Timeout Reduction % per level"
          value={state.move_time_out_mod_bonus_per_level}
          on_change={(value) =>
            onChange('move_time_out_mod_bonus_per_level', value)
          }
          error={errors.move_time_out_mod_bonus_per_level}
          description="A decimal fraction, e.g. 0.01 for 1%."
        />
      </div>

      <FieldWrapper
        id="skill-game-class"
        label="Belongs To Class"
        error={errors.game_class_id}
      >
        {(describedBy) => (
          <Dropdown
            id="skill-game-class"
            aria_label="Belongs To Class"
            aria_described_by={describedBy}
            aria_invalid={!!errors.game_class_id}
            searchable
            items={classItems}
            pre_selected_item={classItems.find(
              (item) => item.value === state.game_class_id
            )}
            on_select={(item) =>
              onChange('game_class_id', parseSkillDropdownValue(item.value))
            }
            on_clear={() => onChange('game_class_id', null)}
            selection_placeholder="None"
          />
        )}
      </FieldWrapper>
    </div>
  );
};

export default SkillTimerModifierFields;
