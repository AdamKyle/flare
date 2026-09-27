import React, { ReactNode } from 'react';

import { skillTypeLabel } from '../../enums/skill-type';
import SkillFormFieldsProps from '../../types/skill-form-fields-props';
import { parseSkillDropdownValue } from '../../utils/parse-skill-dropdown-value';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import CheckboxField from 'ui/forms/checkbox-field';
import FieldWrapper from 'ui/forms/field-wrapper';
import NumberField from 'ui/forms/number-field';
import TextAreaField from 'ui/forms/text-area-field';
import Input from 'ui/input/input';

const SkillBasicFields = ({
  state,
  errors,
  form_options: formOptions,
  on_change: onChange,
}: SkillFormFieldsProps): ReactNode => {
  const typeItems: DropdownItem[] = formOptions.types.map((type) => ({
    label: skillTypeLabel(type),
    value: type,
  }));

  return (
    <div className="space-y-4">
      <FieldWrapper id="skill-name" label="Name" required error={errors.name}>
        {(describedBy) => (
          <Input
            id="skill-name"
            value={state.name}
            on_change={(value) => onChange('name', value)}
            described_by={describedBy}
            invalid={!!errors.name}
            required
          />
        )}
      </FieldWrapper>

      <TextAreaField
        id="skill-description"
        label="Description"
        value={state.description}
        on_change={(value) => onChange('description', value)}
        error={errors.description}
        required
      />

      <NumberField
        id="skill-max-level"
        label="Max Level"
        value={state.max_level}
        on_change={(value) => onChange('max_level', value)}
        error={errors.max_level}
        required
      />

      <CheckboxField
        id="skill-can-train"
        label="Can Skill Be Trained"
        checked={state.can_train}
        on_change={(checked) => onChange('can_train', checked)}
        error={errors.can_train}
      />

      <CheckboxField
        id="skill-is-locked"
        label="Is Skill Locked"
        checked={state.is_locked}
        on_change={(checked) => onChange('is_locked', checked)}
        error={errors.is_locked}
      />

      <FieldWrapper id="skill-type" label="Type" required error={errors.type}>
        {(describedBy) => (
          <Dropdown
            id="skill-type"
            aria_label="Type"
            aria_described_by={describedBy}
            aria_invalid={!!errors.type}
            aria_required
            items={typeItems}
            pre_selected_item={typeItems.find(
              (item) => item.value === state.type
            )}
            on_select={(item) =>
              onChange('type', parseSkillDropdownValue(item.value))
            }
            selection_placeholder="Select a type"
          />
        )}
      </FieldWrapper>
    </div>
  );
};

export default SkillBasicFields;
