import React, { ReactNode } from 'react';

import PassiveSkillFormFieldsProps from '../../types/passive-skill-form-fields-props';
import { parsePassiveSkillDropdownValue } from '../../utils/parse-passive-skill-dropdown-value';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import FieldWrapper from 'ui/forms/field-wrapper';
import NumberField from 'ui/forms/number-field';

const PassiveSkillTreeFields = ({
  state,
  errors,
  form_options: formOptions,
  passive_skill_id: passiveSkillId,
  on_change: onChange,
}: PassiveSkillFormFieldsProps): ReactNode => {
  const parentItems: DropdownItem[] = formOptions.passive_skills
    .filter((passiveSkill) => passiveSkill.id !== passiveSkillId)
    .map((passiveSkill) => ({
      label: passiveSkill.name,
      value: passiveSkill.id,
    }));

  return (
    <div className="space-y-4">
      <FieldWrapper
        id="passive-skill-parent"
        label="Belongs To Skill"
        error={errors.parent_skill_id}
      >
        {(describedBy) => (
          <Dropdown
            id="passive-skill-parent"
            aria_label="Belongs To Skill"
            aria_described_by={describedBy}
            aria_invalid={!!errors.parent_skill_id}
            searchable
            items={parentItems}
            pre_selected_item={parentItems.find(
              (item) => item.value === state.parent_skill_id
            )}
            on_select={(item) =>
              onChange(
                'parent_skill_id',
                parsePassiveSkillDropdownValue(item.value)
              )
            }
            on_clear={() => onChange('parent_skill_id', null)}
            selection_placeholder="None"
          />
        )}
      </FieldWrapper>

      <NumberField
        id="passive-skill-unlocks-at-level"
        label="Unlocks At Level"
        value={state.unlocks_at_level}
        on_change={(value) => onChange('unlocks_at_level', value)}
        error={errors.unlocks_at_level}
        description="The parent Skill level that unlocks this Skill."
      />
    </div>
  );
};

export default PassiveSkillTreeFields;
