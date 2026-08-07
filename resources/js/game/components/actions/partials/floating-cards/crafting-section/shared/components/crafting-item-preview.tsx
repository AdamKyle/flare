import clsx from 'clsx';
import React, { ReactNode } from 'react';

import CraftingItemPreviewProps from './types/crafting-item-preview-props';
import CraftingResultNameButton from './crafting-result-name-button';
import DefinitionRow from '../../../../../../../reusable-components/viewable-sections/definition-row';
import InfoLabel from '../../../../../../../reusable-components/viewable-sections/info-label';
import Section from '../../../../../../../reusable-components/viewable-sections/section';
import StatRowPercent from '../../../../../../../reusable-components/viewable-sections/stat-row-percent';
import { planeTextItemColors } from '../../../../../../character-sheet/partials/character-inventory/styles/backpack-item-styles';
import AmbushCounterSection from '../../../../../../side-peeks/character-inventory/inventory-item/partials/item-view/ambush-and-counter-section';
import AttackSection from '../../../../../../side-peeks/character-inventory/inventory-item/partials/item-view/attack-section';
import DefenceSection from '../../../../../../side-peeks/character-inventory/inventory-item/partials/item-view/defence-section';
import HealingSection from '../../../../../../side-peeks/character-inventory/inventory-item/partials/item-view/healing-section';

const STAT_FIELDS = [
  { key: 'str_modifier' as const, label: 'Strength' },
  { key: 'dur_modifier' as const, label: 'Durability' },
  { key: 'dex_modifier' as const, label: 'Dexterity' },
  { key: 'chr_modifier' as const, label: 'Charisma' },
  { key: 'int_modifier' as const, label: 'Intelligence' },
  { key: 'agi_modifier' as const, label: 'Agility' },
  { key: 'focus_modifier' as const, label: 'Focus' },
] as const;

const CraftingItemPreview = ({
  item,
  display_name,
  on_name_click,
}: CraftingItemPreviewProps): ReactNode => {
  const itemColorClass = planeTextItemColors(item);
  const displayName = display_name ?? item.name;

  const renderName = () => {
    if (!on_name_click) {
      return (
        <p className={clsx('font-semibold', itemColorClass)}>{displayName}</p>
      );
    }

    return (
      <CraftingResultNameButton
        name={displayName}
        class_name={itemColorClass}
        on_click={on_name_click}
      />
    );
  };

  const renderDescription = () => {
    if (!item.description) {
      return null;
    }

    return (
      <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
        {item.description}
      </p>
    );
  };

  const renderAffixes = () => {
    if (!item.item_prefix && !item.item_suffix) {
      return null;
    }

    return (
      <Section title="Enchantments">
        {item.item_prefix && (
          <DefinitionRow
            left={<InfoLabel label="Prefix" />}
            right={
              <span className="text-gray-800 dark:text-gray-200">
                {item.item_prefix.name}
              </span>
            }
          />
        )}
        {item.item_suffix && (
          <DefinitionRow
            left={<InfoLabel label="Suffix" />}
            right={
              <span className="text-gray-800 dark:text-gray-200">
                {item.item_suffix.name}
              </span>
            }
          />
        )}
      </Section>
    );
  };

  const renderStats = () => {
    const hasStats = STAT_FIELDS.some((field) => (item[field.key] ?? 0) > 0);

    if (!hasStats) {
      return null;
    }

    return (
      <Section title="Stats">
        {STAT_FIELDS.map((field) => {
          const value = item[field.key] ?? 0;

          if (value <= 0) {
            return null;
          }

          return (
            <StatRowPercent
              key={field.key}
              label={field.label}
              value={value}
              tooltip={`Adds ${Math.round(value * 100)}% to your ${field.label.toLowerCase()}.`}
            />
          );
        })}
      </Section>
    );
  };

  const renderHolyStacks = () => {
    if (item.holy_stacks <= 0 && item.holy_stacks_applied <= 0) {
      return null;
    }

    return (
      <Section title="Holy Stacks">
        {item.holy_stacks > 0 && (
          <DefinitionRow
            left={<InfoLabel label="Total Holy Stacks" />}
            right={
              <span className="font-semibold tabular-nums">
                {item.holy_stacks}
              </span>
            }
          />
        )}
        {item.holy_stacks_applied > 0 && (
          <DefinitionRow
            left={<InfoLabel label="Applied Holy Stacks" />}
            right={
              <span className="font-semibold tabular-nums">
                {item.holy_stacks_applied}
              </span>
            }
          />
        )}
      </Section>
    );
  };

  const renderSockets = () => {
    if (item.socket_count <= 0) {
      return null;
    }

    return (
      <Section title="Sockets">
        <DefinitionRow
          left={<InfoLabel label="Socket Count" />}
          right={
            <span className="font-semibold tabular-nums">
              {item.socket_count}
            </span>
          }
        />
      </Section>
    );
  };

  return (
    <div className="space-y-2">
      {renderName()}
      {renderDescription()}
      <p className="text-xs text-gray-500 dark:text-gray-400">
        Type: {item.type}
      </p>
      {renderAffixes()}
      <AttackSection
        attack={item.base_damage}
        baseDamageMod={item.base_damage_mod}
      />
      <DefenceSection ac={item.base_ac} baseAcMod={item.base_ac_mod} />
      <HealingSection
        healing={item.base_healing}
        baseHealingMod={item.base_healing_mod}
      />
      {renderStats()}
      <AmbushCounterSection
        ambushChance={item.ambush_chance}
        ambushResistChance={item.ambush_resistance_chance}
        counterChance={item.counter_chance}
        counterResistChance={item.counter_resistance_chance}
      />
      {renderHolyStacks()}
      {renderSockets()}
    </div>
  );
};

export default CraftingItemPreview;
