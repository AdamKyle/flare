import React, { ReactNode } from 'react';

import NpcBasicFieldsProps from '../../types/npc-basic-fields-props';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import FieldWrapper from 'ui/forms/field-wrapper';
import Input from 'ui/input/input';

const NpcBasicFields = ({
  game_map_name: gameMapName,
  state,
  errors,
  form_options: formOptions,
  on_change: onChange,
}: NpcBasicFieldsProps): ReactNode => {
  const typeItems: DropdownItem[] = formOptions.npc_types.map((option) => ({
    label: option.label,
    value: option.value,
  }));

  return (
    <div className="space-y-4">
      <div>
        <p className="text-glacier-900 dark:text-glacier-100 mb-1 text-sm font-medium">
          Game Map
        </p>
        <p className="text-glacier-600 dark:text-glacier-300 text-sm">
          {gameMapName}
        </p>
      </div>

      <FieldWrapper
        id="npc-real-name"
        label="Name"
        required
        error={errors.real_name}
      >
        {(describedBy) => (
          <Input
            id="npc-real-name"
            value={state.real_name}
            on_change={(value) => onChange('real_name', value)}
            described_by={describedBy}
            invalid={!!errors.real_name}
            required
          />
        )}
      </FieldWrapper>

      <FieldWrapper id="npc-type" label="Npc Type" required error={errors.type}>
        {(describedBy) => (
          <Dropdown
            id="npc-type"
            aria_label="Npc Type"
            aria_described_by={describedBy}
            aria_invalid={!!errors.type}
            aria_required
            items={typeItems}
            pre_selected_item={typeItems.find(
              (item) => item.value === state.type
            )}
            on_select={(item) => onChange('type', Number(item.value))}
            selection_placeholder="Select an Npc type"
          />
        )}
      </FieldWrapper>
    </div>
  );
};

export default NpcBasicFields;
