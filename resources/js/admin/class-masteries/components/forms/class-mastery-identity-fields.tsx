import React, { ReactNode } from 'react';

import ClassMasteryFormFieldsProps from '../../types/class-mastery-form-fields-props';
import { parseNumberOption } from '../../utils/parse-class-mastery-dropdown-value';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import FieldWrapper from 'ui/forms/field-wrapper';
import NumberField from 'ui/forms/number-field';
import TextAreaField from 'ui/forms/text-area-field';
import Input from 'ui/input/input';

const ClassMasteryIdentityFields = ({
  state,
  errors,
  form_options: formOptions,
  on_change: onChange,
}: ClassMasteryFormFieldsProps): ReactNode => {
  const classItems: DropdownItem[] = formOptions.classes.map((gameClass) => ({
    label: gameClass.name,
    value: gameClass.id,
  }));

  return (
    <div className="space-y-4">
      <FieldWrapper
        id="class-mastery-game-class"
        label="Class"
        required
        error={errors.game_class_id}
      >
        {(describedBy) => (
          <Dropdown
            id="class-mastery-game-class"
            aria_label="Class"
            aria_described_by={describedBy}
            aria_invalid={!!errors.game_class_id}
            aria_required
            searchable
            items={classItems}
            pre_selected_item={classItems.find(
              (item) => item.value === state.game_class_id
            )}
            on_select={(item) =>
              onChange('game_class_id', parseNumberOption(item.value))
            }
            selection_placeholder="Select a Class"
          />
        )}
      </FieldWrapper>

      <FieldWrapper
        id="class-mastery-name"
        label="Name"
        required
        error={errors.name}
      >
        {(describedBy) => (
          <Input
            id="class-mastery-name"
            value={state.name}
            on_change={(value) => onChange('name', value)}
            described_by={describedBy}
            invalid={!!errors.name}
            required
          />
        )}
      </FieldWrapper>

      <TextAreaField
        id="class-mastery-description"
        label="Description"
        value={state.description}
        on_change={(value) => onChange('description', value)}
        error={errors.description}
        required
      />

      <NumberField
        id="class-mastery-requires-class-rank-level"
        label="Requires Class Rank Level"
        value={state.requires_class_rank_level}
        on_change={(value) => onChange('requires_class_rank_level', value)}
        min={0}
        error={errors.requires_class_rank_level}
      />
    </div>
  );
};

export default ClassMasteryIdentityFields;
