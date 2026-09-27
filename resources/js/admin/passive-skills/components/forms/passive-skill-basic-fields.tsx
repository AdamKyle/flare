import React, { ReactNode } from 'react';

import PassiveSkillFormFieldsProps from '../../types/passive-skill-form-fields-props';
import { parsePassiveSkillDropdownValue } from '../../utils/parse-passive-skill-dropdown-value';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import CheckboxField from 'ui/forms/checkbox-field';
import FieldWrapper from 'ui/forms/field-wrapper';
import NumberField from 'ui/forms/number-field';
import TextAreaField from 'ui/forms/text-area-field';
import Input from 'ui/input/input';

const PassiveSkillBasicFields = ({
  state,
  errors,
  form_options: formOptions,
  on_change: onChange,
}: PassiveSkillFormFieldsProps): ReactNode => {
  const effectItems: DropdownItem[] = formOptions.effects.map((effect) => ({
    label: effect.name,
    value: effect.value,
  }));

  return (
    <div className="space-y-4">
      <FieldWrapper
        id="passive-skill-name"
        label="Name"
        required
        error={errors.name}
      >
        {(describedBy) => (
          <Input
            id="passive-skill-name"
            value={state.name}
            on_change={(value) => onChange('name', value)}
            described_by={describedBy}
            invalid={!!errors.name}
            required
          />
        )}
      </FieldWrapper>

      <TextAreaField
        id="passive-skill-description"
        label="Description"
        value={state.description}
        on_change={(value) => onChange('description', value)}
        error={errors.description}
        required
      />

      <FieldWrapper
        id="passive-skill-effect"
        label="Effect"
        required
        error={errors.effect_type}
      >
        {(describedBy) => (
          <Dropdown
            id="passive-skill-effect"
            aria_label="Effect"
            aria_described_by={describedBy}
            aria_invalid={!!errors.effect_type}
            aria_required
            searchable
            items={effectItems}
            pre_selected_item={effectItems.find(
              (item) => item.value === state.effect_type
            )}
            on_select={(item) =>
              onChange(
                'effect_type',
                parsePassiveSkillDropdownValue(item.value)
              )
            }
            selection_placeholder="Select an effect"
          />
        )}
      </FieldWrapper>

      <div className="grid gap-4 md:grid-cols-2">
        <NumberField
          id="passive-skill-max-level"
          label="Max Level"
          value={state.max_level}
          on_change={(value) => onChange('max_level', value)}
          error={errors.max_level}
          required
        />
        <NumberField
          id="passive-skill-hours-per-level"
          label="Hours Per Level"
          value={state.hours_per_level}
          on_change={(value) => onChange('hours_per_level', value)}
          error={errors.hours_per_level}
          required
        />
      </div>

      <CheckboxField
        id="passive-skill-is-locked"
        label="Is Locked"
        checked={state.is_locked}
        on_change={(checked) => onChange('is_locked', checked)}
        error={errors.is_locked}
      />

      <CheckboxField
        id="passive-skill-is-parent"
        label="Is Parent Skill"
        checked={state.is_parent}
        on_change={(checked) => onChange('is_parent', checked)}
        error={errors.is_parent}
      />
    </div>
  );
};

export default PassiveSkillBasicFields;
