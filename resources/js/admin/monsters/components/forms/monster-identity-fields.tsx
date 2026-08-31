import React, { ReactNode } from 'react';

import {
  isLocationType,
  LOCATION_TYPE_LABELS,
} from '../../../locations/enums/location-type';
import MonsterFormFieldsProps from '../../types/monster-form-fields-props';
import { parseNumberOption } from '../../utils/parse-monster-dropdown-value';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import FieldWrapper from 'ui/forms/field-wrapper';
import NumberField from 'ui/forms/number-field';
import Input from 'ui/input/input';

const DAMAGE_STAT_LABELS: Record<string, string> = {
  str: 'Strength',
  dur: 'Durability',
  dex: 'Dexterity',
  chr: 'Charisma',
  int: 'Intelligence',
  agi: 'Agility',
  focus: 'Focus',
};

const MonsterIdentityFields = ({
  state,
  errors,
  form_options: formOptions,
  on_change: onChange,
}: MonsterFormFieldsProps): ReactNode => {
  const damageStatItems: DropdownItem[] = formOptions.damage_stats.map(
    (stat) => ({ label: DAMAGE_STAT_LABELS[stat] ?? stat, value: stat })
  );
  const gameMapItems: DropdownItem[] = formOptions.game_maps;
  const locationTypeItems: DropdownItem[] = formOptions.location_types.map(
    (value) => ({
      label: isLocationType(value)
        ? LOCATION_TYPE_LABELS[value]
        : `Location Type ${value}`,
      value,
    })
  );

  return (
    <div className="space-y-4">
      <FieldWrapper id="monster-name" label="Name" required error={errors.name}>
        {(describedBy) => (
          <Input
            id="monster-name"
            value={state.name}
            on_change={(value) => onChange('name', value)}
            described_by={describedBy}
            invalid={!!errors.name}
            required
          />
        )}
      </FieldWrapper>

      <FieldWrapper
        id="monster-damage-stat"
        label="Damage Stat"
        required
        error={errors.damage_stat}
      >
        {(describedBy) => (
          <Dropdown
            id="monster-damage-stat"
            aria_label="Damage Stat"
            aria_described_by={describedBy}
            aria_invalid={!!errors.damage_stat}
            aria_required
            items={damageStatItems}
            pre_selected_item={damageStatItems.find(
              (item) => item.value === state.damage_stat
            )}
            on_select={(item) => onChange('damage_stat', String(item.value))}
            selection_placeholder="Select a stat"
          />
        )}
      </FieldWrapper>

      <FieldWrapper
        id="monster-game-map"
        label="Game Map"
        required
        error={errors.game_map_id}
      >
        {(describedBy) => (
          <Dropdown
            id="monster-game-map"
            aria_label="Game Map"
            aria_described_by={describedBy}
            aria_invalid={!!errors.game_map_id}
            aria_required
            searchable
            items={gameMapItems}
            pre_selected_item={gameMapItems.find(
              (item) => item.value === state.game_map_id
            )}
            on_select={(item) =>
              onChange('game_map_id', parseNumberOption(item.value))
            }
            selection_placeholder="Select a Game Map"
          />
        )}
      </FieldWrapper>

      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        <NumberField
          id="monster-max-level"
          label="Max Level"
          value={state.max_level}
          on_change={(value) => onChange('max_level', value)}
          min={0}
          error={errors.max_level}
        />
        <NumberField
          id="monster-xp"
          label="XP"
          value={state.xp}
          on_change={(value) => onChange('xp', value)}
          min={0}
          error={errors.xp}
        />
        <NumberField
          id="monster-gold"
          label="Gold"
          value={state.gold}
          on_change={(value) => onChange('gold', value)}
          min={0}
          error={errors.gold}
        />
      </div>

      <div className="grid gap-4 md:grid-cols-2">
        <FieldWrapper
          id="monster-health-range"
          label="Health Range"
          error={errors.health_range}
        >
          {(describedBy) => (
            <Input
              id="monster-health-range"
              value={state.health_range}
              on_change={(value) => onChange('health_range', value)}
              described_by={describedBy}
              invalid={!!errors.health_range}
              place_holder="e.g. 1-8"
            />
          )}
        </FieldWrapper>
        <FieldWrapper
          id="monster-attack-range"
          label="Attack Range"
          error={errors.attack_range}
        >
          {(describedBy) => (
            <Input
              id="monster-attack-range"
              value={state.attack_range}
              on_change={(value) => onChange('attack_range', value)}
              described_by={describedBy}
              invalid={!!errors.attack_range}
              place_holder="e.g. 1-6"
            />
          )}
        </FieldWrapper>
      </div>

      <NumberField
        id="monster-drop-check"
        label="Drop Check"
        value={state.drop_check}
        on_change={(value) => onChange('drop_check', value)}
        min={0}
        error={errors.drop_check}
      />

      <FieldWrapper id="monster-location-type" label="Only For Location Type">
        {(describedBy) => (
          <Dropdown
            id="monster-location-type"
            aria_label="Only For Location Type"
            aria_described_by={describedBy}
            items={locationTypeItems}
            pre_selected_item={locationTypeItems.find(
              (item) => item.value === state.only_for_location_type
            )}
            on_select={(item) =>
              onChange('only_for_location_type', parseNumberOption(item.value))
            }
            on_clear={() => onChange('only_for_location_type', null)}
            selection_placeholder="None"
          />
        )}
      </FieldWrapper>
    </div>
  );
};

export default MonsterIdentityFields;
