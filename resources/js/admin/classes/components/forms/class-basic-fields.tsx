import React, { ReactNode } from 'react';

import { coreStatLabel } from '../../enums/core-stat';
import ClassFormFieldsProps from '../../types/class-form-fields-props';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import FieldWrapper from 'ui/forms/field-wrapper';
import TextAreaField from 'ui/forms/text-area-field';
import Input from 'ui/input/input';

const ClassBasicFields = ({
  state,
  errors,
  form_options: formOptions,
  on_change: onChange,
}: ClassFormFieldsProps): ReactNode => {
  const statItems: DropdownItem[] = formOptions.stats.map((stat) => ({
    label: coreStatLabel(stat),
    value: stat,
  }));

  return (
    <div className="space-y-4">
      <FieldWrapper id="class-name" label="Name" required error={errors.name}>
        {(describedBy) => (
          <Input
            id="class-name"
            value={state.name}
            on_change={(value) => onChange('name', value)}
            described_by={describedBy}
            invalid={!!errors.name}
            required
          />
        )}
      </FieldWrapper>

      <TextAreaField
        id="class-description"
        label="Description"
        value={state.description}
        on_change={(value) => onChange('description', value)}
        error={errors.description}
      />

      <FieldWrapper
        id="class-damage-stat"
        label="Damage Stat"
        required
        error={errors.damage_stat}
      >
        {(describedBy) => (
          <Dropdown
            id="class-damage-stat"
            aria_label="Damage Stat"
            aria_described_by={describedBy}
            aria_invalid={!!errors.damage_stat}
            aria_required
            items={statItems}
            pre_selected_item={statItems.find(
              (item) => item.value === state.damage_stat
            )}
            on_select={(item) => onChange('damage_stat', String(item.value))}
            selection_placeholder="Select a stat"
          />
        )}
      </FieldWrapper>

      <FieldWrapper
        id="class-to-hit-stat"
        label="To Hit Stat"
        required
        error={errors.to_hit_stat}
      >
        {(describedBy) => (
          <Dropdown
            id="class-to-hit-stat"
            aria_label="To Hit Stat"
            aria_described_by={describedBy}
            aria_invalid={!!errors.to_hit_stat}
            aria_required
            items={statItems}
            pre_selected_item={statItems.find(
              (item) => item.value === state.to_hit_stat
            )}
            on_select={(item) => onChange('to_hit_stat', String(item.value))}
            selection_placeholder="Select a stat"
          />
        )}
      </FieldWrapper>
    </div>
  );
};

export default ClassBasicFields;
