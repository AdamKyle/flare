import React, { ReactNode } from 'react';

import {
  ITEM_CRAFTING_TYPE_LABELS,
  isItemCraftingType,
} from '../../enums/item-crafting-type';
import ItemFormFieldsProps from '../../types/item-form-fields-props';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import CheckboxField from 'ui/forms/checkbox-field';
import FieldWrapper from 'ui/forms/field-wrapper';
import NumberField from 'ui/forms/number-field';

const ItemCraftingFields = ({
  state,
  errors,
  form_options: formOptions,
  on_change: onChange,
}: ItemFormFieldsProps): ReactNode => {
  const craftingTypeItems: DropdownItem[] = formOptions.crafting_types.map(
    (type) => ({ label: ITEM_CRAFTING_TYPE_LABELS[type], value: type })
  );

  const renderCraftingFields = (): ReactNode => {
    if (!state.can_craft) {
      return null;
    }

    return (
      <>
        <CheckboxField
          id="item-craft-only"
          label="Craft Only (not otherwise obtainable)"
          checked={state.craft_only}
          on_change={(value) => onChange('craft_only', value)}
        />

        <FieldWrapper id="item-crafting-type" label="Crafting Type">
          {(describedBy) => (
            <Dropdown
              id="item-crafting-type"
              aria_label="Crafting Type"
              aria_described_by={describedBy}
              items={craftingTypeItems}
              pre_selected_item={craftingTypeItems.find(
                (item) => item.value === state.crafting_type
              )}
              on_select={(item) => {
                if (!isItemCraftingType(item.value)) {
                  return;
                }

                onChange('crafting_type', item.value);
              }}
              on_clear={() => onChange('crafting_type', '')}
              selection_placeholder="None"
            />
          )}
        </FieldWrapper>

        <div className="grid gap-4 md:grid-cols-2">
          <NumberField
            id="item-skill-level-required"
            label="Minimum Crafting Level"
            value={state.skill_level_required}
            on_change={(value) => onChange('skill_level_required', value)}
            error={errors.skill_level_required}
            min={0}
          />
          <NumberField
            id="item-skill-level-trivial"
            label="Trivial Crafting Level"
            value={state.skill_level_trivial}
            on_change={(value) => onChange('skill_level_trivial', value)}
            error={errors.skill_level_trivial}
            min={0}
          />
        </div>
      </>
    );
  };

  return (
    <div className="space-y-4">
      <CheckboxField
        id="item-can-craft"
        label="Can Craft"
        checked={state.can_craft}
        on_change={(value) => onChange('can_craft', value)}
      />

      {renderCraftingFields()}
    </div>
  );
};

export default ItemCraftingFields;
