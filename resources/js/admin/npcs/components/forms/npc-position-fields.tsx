import React, { ReactNode } from 'react';

import NpcPositionFieldsProps from '../../types/npc-position-fields-props';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import FieldWrapper from 'ui/forms/field-wrapper';

const NpcPositionFields = ({
  state,
  errors,
  form_options: formOptions,
  on_change: onChange,
}: NpcPositionFieldsProps): ReactNode => {
  const xItems: DropdownItem[] = formOptions.coordinates.x.map((value) => ({
    label: String(value),
    value,
  }));
  const yItems: DropdownItem[] = formOptions.coordinates.y.map((value) => ({
    label: String(value),
    value,
  }));

  return (
    <div className="grid gap-4 md:grid-cols-2">
      <FieldWrapper
        id="npc-x"
        label="X Coordinate"
        required
        error={errors.x_position}
      >
        {(describedBy) => (
          <Dropdown
            id="npc-x"
            aria_label="X Coordinate"
            aria_described_by={describedBy}
            aria_invalid={!!errors.x_position}
            aria_required
            items={xItems}
            pre_selected_item={xItems.find(
              (item) => item.value === state.x_position
            )}
            on_select={(item) => onChange('x_position', Number(item.value))}
            selection_placeholder="Select an X coordinate"
          />
        )}
      </FieldWrapper>

      <FieldWrapper
        id="npc-y"
        label="Y Coordinate"
        required
        error={errors.y_position}
      >
        {(describedBy) => (
          <Dropdown
            id="npc-y"
            aria_label="Y Coordinate"
            aria_described_by={describedBy}
            aria_invalid={!!errors.y_position}
            aria_required
            items={yItems}
            pre_selected_item={yItems.find(
              (item) => item.value === state.y_position
            )}
            on_select={(item) => onChange('y_position', Number(item.value))}
            selection_placeholder="Select a Y coordinate"
          />
        )}
      </FieldWrapper>
    </div>
  );
};

export default NpcPositionFields;
