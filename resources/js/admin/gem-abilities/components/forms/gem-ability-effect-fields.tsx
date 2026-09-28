import React, { ReactNode } from 'react';

import { attackTypeLabel } from '../../../../game/reusable-components/class-mastery/enums/attack-type';
import { gemAbilityEffectTypeLabel } from '../../../../game/reusable-components/gem-ability/enums/gem-ability-effect-type';
import { gemAbilityScalingSourceLabel } from '../../../../game/reusable-components/gem-ability/enums/gem-ability-scaling-source';
import GemAbilityAttackTypeOption from '../../types/gem-ability-attack-type-option';
import GemAbilityFormFieldsProps from '../../types/gem-ability-form-fields-props';
import { isActiveAbilityType } from '../../utils/gem-ability-form-state';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import CheckboxField from 'ui/forms/checkbox-field';
import FieldWrapper from 'ui/forms/field-wrapper';
import NumberField from 'ui/forms/number-field';

const GemAbilityEffectFields = ({
  state,
  errors,
  form_options: formOptions,
  on_change: onChange,
}: GemAbilityFormFieldsProps): ReactNode => {
  const isActive = isActiveAbilityType(state.ability_type);

  const effectTypes = isActive
    ? formOptions.active_effect_types
    : formOptions.passive_effect_types;

  const effectTypeItems: DropdownItem[] = effectTypes.map((effectType) => ({
    label: gemAbilityEffectTypeLabel(effectType),
    value: effectType,
  }));

  const scalingSourceItems: DropdownItem[] = formOptions.scaling_sources.map(
    (scalingSource) => ({
      label: gemAbilityScalingSourceLabel(scalingSource),
      value: scalingSource,
    })
  );

  const attackTypeOptions: GemAbilityAttackTypeOption[] =
    formOptions.attack_types.map((attackType) => ({
      id: `gem-ability-attack-type-${attackType.replaceAll('_', '-')}`,
      value: attackType,
      label: attackTypeLabel(attackType),
      checked: state.attack_types.includes(attackType),
    }));

  const handleEffectTypeSelect = (item: DropdownItem): void => {
    onChange(
      'effect_type',
      effectTypes.find((candidate) => candidate === item.value) ?? null
    );
  };

  const handleScalingSourceSelect = (item: DropdownItem): void => {
    onChange(
      'scaling_source',
      formOptions.scaling_sources.find(
        (candidate) => candidate === item.value
      ) ?? null
    );
  };

  const handleAttackTypeToggle = (attackType: string, checked: boolean) => {
    const remainingAttackTypes = state.attack_types.filter(
      (selectedAttackType) => selectedAttackType !== attackType
    );

    onChange(
      'attack_types',
      checked ? [...remainingAttackTypes, attackType] : remainingAttackTypes
    );
  };

  const renderAttackTypeOption = (
    option: GemAbilityAttackTypeOption
  ): ReactNode => (
    <CheckboxField
      key={option.value}
      id={option.id}
      label={option.label}
      checked={option.checked}
      on_change={(checked) => handleAttackTypeToggle(option.value, checked)}
    />
  );

  const renderAttackTypesError = (): ReactNode => {
    if (!errors.attack_types) {
      return null;
    }

    return (
      <p role="alert" className="text-xs text-rose-600 dark:text-rose-400">
        {errors.attack_types}
      </p>
    );
  };

  const renderActiveFields = (): ReactNode => {
    if (!isActive) {
      return null;
    }

    return (
      <>
        <NumberField
          id="gem-ability-proc-chance"
          label="Proc Chance"
          description="A decimal chance from 0.01 to 1 (0.20 is 20%)."
          value={state.proc_chance}
          on_change={(value) => onChange('proc_chance', value)}
          required
          error={errors.proc_chance}
        />

        <FieldWrapper
          id="gem-ability-scaling-source"
          label="Scaling Source"
          required
          error={errors.scaling_source}
        >
          {(describedBy) => (
            <Dropdown
              id="gem-ability-scaling-source"
              aria_label="Scaling Source"
              aria_described_by={describedBy}
              aria_invalid={!!errors.scaling_source}
              aria_required
              items={scalingSourceItems}
              pre_selected_item={scalingSourceItems.find(
                (item) => item.value === state.scaling_source
              )}
              on_select={handleScalingSourceSelect}
              selection_placeholder="Select a scaling source"
            />
          )}
        </FieldWrapper>
      </>
    );
  };

  return (
    <div className="space-y-4">
      <FieldWrapper
        id="gem-ability-effect-type"
        label="Effect"
        required
        error={errors.effect_type}
      >
        {(describedBy) => (
          <Dropdown
            id="gem-ability-effect-type"
            aria_label="Effect"
            aria_described_by={describedBy}
            aria_invalid={!!errors.effect_type}
            aria_required
            items={effectTypeItems}
            pre_selected_item={effectTypeItems.find(
              (item) => item.value === state.effect_type
            )}
            on_select={handleEffectTypeSelect}
            selection_placeholder="Select an effect"
          />
        )}
      </FieldWrapper>

      <fieldset className="space-y-1">
        <legend className="mb-2 text-sm font-medium text-gray-800 dark:text-gray-200">
          Allowed Attack Actions
        </legend>
        {attackTypeOptions.map(renderAttackTypeOption)}
        {renderAttackTypesError()}
      </fieldset>

      <NumberField
        id="gem-ability-effect-value"
        label="Effect Value"
        description="A decimal from 0.01 to 1. Active abilities deal this fraction of the scaling source; passive abilities add this percentage modifier."
        value={state.effect_value}
        on_change={(value) => onChange('effect_value', value)}
        required
        error={errors.effect_value}
      />

      {renderActiveFields()}
    </div>
  );
};

export default GemAbilityEffectFields;
