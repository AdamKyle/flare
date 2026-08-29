import React, { ReactNode } from 'react';

import ItemFormFieldsProps from '../../types/item-form-fields-props';
import {
  ITEM_SKILL_TYPE_LABELS,
  isItemSkillType,
} from '../../enums/item-skill-type';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import CheckboxField from 'ui/forms/checkbox-field';
import FieldWrapper from 'ui/forms/field-wrapper';
import NumberField from 'ui/forms/number-field';

const ItemUsableFields = ({
  state,
  errors,
  form_options: formOptions,
  on_change: onChange,
}: ItemFormFieldsProps): ReactNode => {
  const skillTypeItems: DropdownItem[] = formOptions.skill_types.map(
    (type) => ({ label: ITEM_SKILL_TYPE_LABELS[type], value: type })
  );

  return (
    <div className="space-y-4">
      <CheckboxField
        id="item-usable"
        label="Usable"
        checked={state.usable}
        on_change={(value) => onChange('usable', value)}
      />

      {state.usable && (
        <>
          <CheckboxField
            id="item-can-stack"
            label="Can Stack"
            checked={state.can_stack}
            on_change={(value) => onChange('can_stack', value)}
          />

          <NumberField
            id="item-lasts-for"
            label="Lasts For (Minutes)"
            value={state.lasts_for}
            on_change={(value) => onChange('lasts_for', value)}
            error={errors.lasts_for}
            min={0}
          />

          <CheckboxField
            id="item-stat-increase"
            label="Increases a Stat"
            checked={state.stat_increase}
            on_change={(value) => onChange('stat_increase', value)}
          />

          {state.stat_increase && (
            <NumberField
              id="item-increase-stat-by"
              label="Increase Stat By"
              value={state.increase_stat_by}
              on_change={(value) => onChange('increase_stat_by', value)}
              error={errors.increase_stat_by}
            />
          )}

          <CheckboxField
            id="item-damages-kingdoms"
            label="Damages Kingdoms"
            checked={state.damages_kingdoms}
            on_change={(value) => onChange('damages_kingdoms', value)}
          />

          {state.damages_kingdoms && (
            <NumberField
              id="item-kingdom-damage"
              label="Kingdom Damage"
              value={state.kingdom_damage}
              on_change={(value) => onChange('kingdom_damage', value)}
              error={errors.kingdom_damage}
              min={0}
            />
          )}

          <FieldWrapper id="item-affects-skill-type" label="Affects Skill">
            {(describedBy) => (
              <Dropdown
                id="item-affects-skill-type"
                aria_label="Affects Skill"
                aria_described_by={describedBy}
                items={skillTypeItems}
                pre_selected_item={skillTypeItems.find(
                  (item) => item.value === state.affects_skill_type
                )}
                on_select={(item) => {
                  if (!isItemSkillType(item.value)) {
                    return;
                  }

                  onChange('affects_skill_type', item.value);
                }}
                on_clear={() => onChange('affects_skill_type', null)}
                selection_placeholder="None"
              />
            )}
          </FieldWrapper>

          {state.affects_skill_type !== null && (
            <div className="grid gap-4 md:grid-cols-2">
              <NumberField
                id="item-increase-skill-bonus-by"
                label="Increase Skill Bonus By"
                value={state.increase_skill_bonus_by}
                on_change={(value) =>
                  onChange('increase_skill_bonus_by', value)
                }
                error={errors.increase_skill_bonus_by}
              />
              <NumberField
                id="item-increase-skill-training-bonus-by"
                label="Increase Skill Training Bonus By"
                value={state.increase_skill_training_bonus_by}
                on_change={(value) =>
                  onChange('increase_skill_training_bonus_by', value)
                }
                error={errors.increase_skill_training_bonus_by}
              />
            </div>
          )}

          <CheckboxField
            id="item-can-use-on-other-items"
            label="Can Use On Other Items (Holy Oil)"
            checked={state.can_use_on_other_items}
            on_change={(value) => onChange('can_use_on_other_items', value)}
          />

          {state.can_use_on_other_items && (
            <NumberField
              id="item-holy-level"
              label="Holy Level"
              value={state.holy_level}
              on_change={(value) => onChange('holy_level', value)}
              error={errors.holy_level}
              min={0}
            />
          )}

          <CheckboxField
            id="item-gains-additional-level"
            label="Gains Additional Level"
            checked={state.gains_additional_level}
            on_change={(value) => onChange('gains_additional_level', value)}
          />
        </>
      )}
    </div>
  );
};

export default ItemUsableFields;
