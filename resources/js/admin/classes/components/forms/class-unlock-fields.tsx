import React, { ReactNode } from 'react';

import ClassFormFieldsProps from '../../types/class-form-fields-props';
import { parseNumberOption } from '../../utils/parse-class-dropdown-value';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import FieldWrapper from 'ui/forms/field-wrapper';
import NumberField from 'ui/forms/number-field';

const ClassUnlockFields = ({
  state,
  errors,
  form_options: formOptions,
  on_change: onChange,
}: ClassFormFieldsProps): ReactNode => {
  const classItems: DropdownItem[] = formOptions.classes.map((gameClass) => ({
    label: gameClass.name,
    value: gameClass.id,
  }));

  return (
    <div className="space-y-4">
      <p className="text-glacier-700 dark:text-glacier-300 text-sm">
        Leave every field below empty for a normal Class. To create a special,
        locked Class, populate all four fields.
      </p>

      <FieldWrapper
        id="class-primary-required-class"
        label="Primary Required Class"
        error={errors.primary_required_class_id}
      >
        {(describedBy) => (
          <Dropdown
            id="class-primary-required-class"
            aria_label="Primary Required Class"
            aria_described_by={describedBy}
            aria_invalid={!!errors.primary_required_class_id}
            searchable
            items={classItems}
            pre_selected_item={classItems.find(
              (item) => item.value === state.primary_required_class_id
            )}
            on_select={(item) =>
              onChange(
                'primary_required_class_id',
                parseNumberOption(item.value)
              )
            }
            on_clear={() => onChange('primary_required_class_id', null)}
            selection_placeholder="None"
          />
        )}
      </FieldWrapper>

      <NumberField
        id="class-primary-required-level"
        label="Primary Required Level"
        value={state.primary_required_class_level}
        on_change={(value) => onChange('primary_required_class_level', value)}
        min={1}
        error={errors.primary_required_class_level}
      />

      <FieldWrapper
        id="class-secondary-required-class"
        label="Secondary Required Class"
        error={errors.secondary_required_class_id}
      >
        {(describedBy) => (
          <Dropdown
            id="class-secondary-required-class"
            aria_label="Secondary Required Class"
            aria_described_by={describedBy}
            aria_invalid={!!errors.secondary_required_class_id}
            searchable
            items={classItems}
            pre_selected_item={classItems.find(
              (item) => item.value === state.secondary_required_class_id
            )}
            on_select={(item) =>
              onChange(
                'secondary_required_class_id',
                parseNumberOption(item.value)
              )
            }
            on_clear={() => onChange('secondary_required_class_id', null)}
            selection_placeholder="None"
          />
        )}
      </FieldWrapper>

      <NumberField
        id="class-secondary-required-level"
        label="Secondary Required Level"
        value={state.secondary_required_class_level}
        on_change={(value) => onChange('secondary_required_class_level', value)}
        min={1}
        error={errors.secondary_required_class_level}
      />
    </div>
  );
};

export default ClassUnlockFields;
