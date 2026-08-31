import React, { ReactNode } from 'react';

import MonsterFormFieldsProps from '../../types/monster-form-fields-props';
import { parseNumberOption } from '../../utils/parse-monster-dropdown-value';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import CheckboxField from 'ui/forms/checkbox-field';
import FieldWrapper from 'ui/forms/field-wrapper';
import NumberField from 'ui/forms/number-field';

const MonsterQuestCelestialFields = ({
  state,
  errors,
  form_options: formOptions,
  on_change: onChange,
}: MonsterFormFieldsProps): ReactNode => {
  const questItemItems: DropdownItem[] = formOptions.quest_items;

  return (
    <div className="space-y-4">
      <FieldWrapper id="monster-quest-item" label="Quest Item">
        {(describedBy) => (
          <Dropdown
            id="monster-quest-item"
            aria_label="Quest Item"
            aria_described_by={describedBy}
            searchable
            items={questItemItems}
            pre_selected_item={questItemItems.find(
              (item) => item.value === state.quest_item_id
            )}
            on_select={(item) =>
              onChange('quest_item_id', parseNumberOption(item.value))
            }
            on_clear={() => onChange('quest_item_id', null)}
            selection_placeholder="None"
          />
        )}
      </FieldWrapper>

      <NumberField
        id="monster-quest-item-drop-chance"
        label="Quest Item Drop Chance"
        value={state.quest_item_drop_chance}
        on_change={(value) => onChange('quest_item_drop_chance', value)}
        min={0}
        disabled={state.quest_item_id === null}
        error={errors.quest_item_drop_chance}
      />

      <CheckboxField
        id="monster-is-celestial-entity"
        label="Celestial Entity"
        checked={state.is_celestial_entity}
        on_change={(value) => onChange('is_celestial_entity', value)}
      />

      <NumberField
        id="monster-celestial-type"
        label="Celestial Type"
        value={state.celestial_type}
        on_change={(value) => onChange('celestial_type', value)}
        min={0}
        error={errors.celestial_type}
      />

      <div className="grid gap-4 md:grid-cols-3">
        <NumberField
          id="monster-gold-cost"
          label="Gold Cost"
          value={state.gold_cost}
          on_change={(v) => onChange('gold_cost', v)}
          min={0}
          error={errors.gold_cost}
        />
        <NumberField
          id="monster-gold-dust-cost"
          label="Gold Dust Cost"
          value={state.gold_dust_cost}
          on_change={(v) => onChange('gold_dust_cost', v)}
          min={0}
          error={errors.gold_dust_cost}
        />
        <NumberField
          id="monster-shards"
          label="Shards"
          value={state.shards}
          on_change={(v) => onChange('shards', v)}
          min={0}
          error={errors.shards}
        />
      </div>
    </div>
  );
};

export default MonsterQuestCelestialFields;
