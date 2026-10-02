import React, { ReactNode } from 'react';

import {
  isRaidAttackType,
  RAID_ATTACK_TYPE_LABELS,
} from '../../../../game/reusable-components/monster/enums/raid-attack-type';
import MonsterFormFieldsProps from '../../types/monster-form-fields-props';
import { parseNumberOption } from '../../utils/parse-monster-dropdown-value';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import CheckboxField from 'ui/forms/checkbox-field';
import FieldWrapper from 'ui/forms/field-wrapper';
import NumberField from 'ui/forms/number-field';

const MonsterRaidFields = ({
  state,
  errors,
  form_options: formOptions,
  on_change: onChange,
}: MonsterFormFieldsProps): ReactNode => {
  const attackTypeItems: DropdownItem[] =
    formOptions.raid_special_attack_types.map((value) => ({
      label: isRaidAttackType(value)
        ? RAID_ATTACK_TYPE_LABELS[value]
        : `Attack Type ${value}`,
      value,
    }));

  return (
    <div className="space-y-4">
      <CheckboxField
        id="monster-is-raid-monster"
        label="Raid Monster"
        checked={state.is_raid_monster}
        on_change={(value) => onChange('is_raid_monster', value)}
      />
      <CheckboxField
        id="monster-is-raid-boss"
        label="Raid Boss"
        checked={state.is_raid_boss}
        on_change={(value) => onChange('is_raid_boss', value)}
        error={errors.is_raid_boss}
      />

      <FieldWrapper id="monster-raid-attack-type" label="Special Attack Type">
        {(describedBy) => (
          <Dropdown
            id="monster-raid-attack-type"
            aria_label="Special Attack Type"
            aria_described_by={describedBy}
            items={attackTypeItems}
            pre_selected_item={attackTypeItems.find(
              (item) => item.value === state.raid_special_attack_type
            )}
            on_select={(item) =>
              onChange(
                'raid_special_attack_type',
                parseNumberOption(item.value)
              )
            }
            on_clear={() => onChange('raid_special_attack_type', null)}
            selection_placeholder="None"
          />
        )}
      </FieldWrapper>

      <div className="grid gap-4 md:grid-cols-3">
        <NumberField
          id="monster-fire-atonement"
          label="Fire Atonement"
          value={state.fire_atonement}
          on_change={(value) => onChange('fire_atonement', value)}
          min={0}
          error={errors.fire_atonement}
        />
        <NumberField
          id="monster-ice-atonement"
          label="Ice Atonement"
          value={state.ice_atonement}
          on_change={(value) => onChange('ice_atonement', value)}
          min={0}
          error={errors.ice_atonement}
        />
        <NumberField
          id="monster-water-atonement"
          label="Water Atonement"
          value={state.water_atonement}
          on_change={(value) => onChange('water_atonement', value)}
          min={0}
          error={errors.water_atonement}
        />
      </div>

      <div className="grid gap-4 md:grid-cols-3">
        <NumberField
          id="monster-fire-penetration"
          label="Fire Penetration"
          value={state.fire_penetration}
          on_change={(value) => onChange('fire_penetration', value)}
          min={0}
          error={errors.fire_penetration}
        />
        <NumberField
          id="monster-ice-penetration"
          label="Ice Penetration"
          value={state.ice_penetration}
          on_change={(value) => onChange('ice_penetration', value)}
          min={0}
          error={errors.ice_penetration}
        />
        <NumberField
          id="monster-water-penetration"
          label="Water Penetration"
          value={state.water_penetration}
          on_change={(value) => onChange('water_penetration', value)}
          min={0}
          error={errors.water_penetration}
        />
      </div>
    </div>
  );
};

export default MonsterRaidFields;
