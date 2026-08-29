import React, { ReactNode } from 'react';

import ItemFormFieldsProps from '../../types/item-form-fields-props';
import { ItemCatalogType } from '../../enums/item-catalog-type';
import {
  ITEM_EFFECT_TYPE_LABELS,
  isItemEffectType,
} from '../../enums/item-effect-type';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import CheckboxField from 'ui/forms/checkbox-field';
import FieldWrapper from 'ui/forms/field-wrapper';
import NumberField from 'ui/forms/number-field';
import Input from 'ui/input/input';

const ItemQuestEffectFields = ({
  state,
  errors,
  form_options: formOptions,
  on_change: onChange,
}: ItemFormFieldsProps): ReactNode => {
  const effectItems: DropdownItem[] = formOptions.effects.map((type) => ({
    label: ITEM_EFFECT_TYPE_LABELS[type],
    value: type,
  }));
  const locationItems: DropdownItem[] = formOptions.locations.map((option) => ({
    label: option.label,
    value: option.value,
  }));
  const classItems: DropdownItem[] = formOptions.classes.map((option) => ({
    label: option.label,
    value: option.value,
  }));
  const itemSkillItems: DropdownItem[] = formOptions.item_skills.map(
    (option) => ({ label: option.label, value: option.value })
  );

  const isQuestItem = state.type === ItemCatalogType.QUEST;

  return (
    <div className="space-y-4">
      {isQuestItem && (
        <FieldWrapper id="item-effect" label="Quest Effect">
          {(describedBy) => (
            <Dropdown
              id="item-effect"
              aria_label="Quest Effect"
              aria_described_by={describedBy}
              items={effectItems}
              pre_selected_item={effectItems.find(
                (item) => item.value === state.effect
              )}
              on_select={(item) => {
                if (!isItemEffectType(item.value)) {
                  return;
                }

                onChange('effect', item.value);
              }}
              on_clear={() => onChange('effect', '')}
              selection_placeholder="None"
            />
          )}
        </FieldWrapper>
      )}

      <FieldWrapper id="item-drop-location" label="Drop Location">
        {(describedBy) => (
          <Dropdown
            id="item-drop-location"
            aria_label="Drop Location"
            aria_described_by={describedBy}
            searchable
            items={locationItems}
            pre_selected_item={locationItems.find(
              (item) => item.value === state.drop_location_id
            )}
            on_select={(item) =>
              onChange('drop_location_id', Number(item.value))
            }
            on_clear={() => onChange('drop_location_id', null)}
            selection_placeholder="None"
          />
        )}
      </FieldWrapper>

      <FieldWrapper id="item-unlocks-class" label="Unlocks Class">
        {(describedBy) => (
          <Dropdown
            id="item-unlocks-class"
            aria_label="Unlocks Class"
            aria_described_by={describedBy}
            items={classItems}
            pre_selected_item={classItems.find(
              (item) => item.value === state.unlocks_class_id
            )}
            on_select={(item) =>
              onChange('unlocks_class_id', Number(item.value))
            }
            on_clear={() => onChange('unlocks_class_id', null)}
            selection_placeholder="None"
          />
        )}
      </FieldWrapper>

      <FieldWrapper id="item-item-skill" label="Item Skill">
        {(describedBy) => (
          <Dropdown
            id="item-item-skill"
            aria_label="Item Skill"
            aria_described_by={describedBy}
            items={itemSkillItems}
            pre_selected_item={itemSkillItems.find(
              (item) => item.value === state.item_skill_id
            )}
            on_select={(item) => onChange('item_skill_id', Number(item.value))}
            on_clear={() => onChange('item_skill_id', null)}
            selection_placeholder="None"
          />
        )}
      </FieldWrapper>

      <FieldWrapper id="item-skill-name" label="Skill Name">
        {(describedBy) => (
          <Input
            id="item-skill-name"
            value={state.skill_name}
            on_change={(value) => onChange('skill_name', value)}
            described_by={describedBy}
          />
        )}
      </FieldWrapper>

      <div className="grid gap-4 md:grid-cols-3">
        <NumberField
          id="item-skill-bonus"
          label="Skill Bonus"
          value={state.skill_bonus}
          on_change={(value) => onChange('skill_bonus', value)}
          error={errors.skill_bonus}
        />
        <NumberField
          id="item-skill-training-bonus"
          label="Skill Training Bonus"
          value={state.skill_training_bonus}
          on_change={(value) => onChange('skill_training_bonus', value)}
          error={errors.skill_training_bonus}
        />
        <NumberField
          id="item-xp-bonus"
          label="XP Bonus"
          value={state.xp_bonus}
          on_change={(value) => onChange('xp_bonus', value)}
          error={errors.xp_bonus}
        />
        <NumberField
          id="item-fight-time-out-mod-bonus"
          label="Fight Timeout Modifier"
          value={state.fight_time_out_mod_bonus}
          on_change={(value) => onChange('fight_time_out_mod_bonus', value)}
          error={errors.fight_time_out_mod_bonus}
        />
        <NumberField
          id="item-move-time-out-mod-bonus"
          label="Move Timeout Modifier"
          value={state.move_time_out_mod_bonus}
          on_change={(value) => onChange('move_time_out_mod_bonus', value)}
          error={errors.move_time_out_mod_bonus}
        />
      </div>

      <CheckboxField
        id="item-ignores-caps"
        label="Ignores Caps"
        checked={state.ignores_caps}
        on_change={(value) => onChange('ignores_caps', value)}
      />

      <CheckboxField
        id="item-can-resurrect"
        label="Can Resurrect"
        checked={state.can_resurrect}
        on_change={(value) => onChange('can_resurrect', value)}
      />

      {state.can_resurrect && (
        <NumberField
          id="item-resurrection-chance"
          label="Resurrection Chance"
          value={state.resurrection_chance}
          on_change={(value) => onChange('resurrection_chance', value)}
          error={errors.resurrection_chance}
          min={0}
        />
      )}

      <div className="grid gap-4 md:grid-cols-3">
        <NumberField
          id="item-spell-evasion"
          label="Spell Evasion"
          value={state.spell_evasion}
          on_change={(value) => onChange('spell_evasion', value)}
          error={errors.spell_evasion}
          min={0}
        />
        <NumberField
          id="item-artifact-annulment"
          label="Artifact Annulment"
          value={state.artifact_annulment}
          on_change={(value) => onChange('artifact_annulment', value)}
          error={errors.artifact_annulment}
          min={0}
        />
        <NumberField
          id="item-healing-reduction"
          label="Healing Reduction"
          value={state.healing_reduction}
          on_change={(value) => onChange('healing_reduction', value)}
          error={errors.healing_reduction}
          min={0}
        />
        <NumberField
          id="item-affix-damage-reduction"
          label="Affix Damage Reduction"
          value={state.affix_damage_reduction}
          on_change={(value) => onChange('affix_damage_reduction', value)}
          error={errors.affix_damage_reduction}
          min={0}
        />
        <NumberField
          id="item-devouring-light"
          label="Devouring Light"
          value={state.devouring_light}
          on_change={(value) => onChange('devouring_light', value)}
          error={errors.devouring_light}
          min={0}
        />
        <NumberField
          id="item-devouring-darkness"
          label="Devouring Darkness"
          value={state.devouring_darkness}
          on_change={(value) => onChange('devouring_darkness', value)}
          error={errors.devouring_darkness}
          min={0}
        />
      </div>
    </div>
  );
};

export default ItemQuestEffectFields;
