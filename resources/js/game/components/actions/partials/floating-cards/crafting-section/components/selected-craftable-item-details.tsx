import clsx from 'clsx';
import React, { ReactNode } from 'react';

import SelectedCraftableItemDetailsProps from './types/selected-craftable-item-details-props';
import { BaseItemDetails } from '../../../../../../api-definitions/items/base-item-details';
import Section from '../../../../../../reusable-components/viewable-sections/section';
import StatRowPercent from '../../../../../../reusable-components/viewable-sections/stat-row-percent';
import { planeTextItemColors } from '../../../../../character-sheet/partials/character-inventory/styles/backpack-item-styles';
import AmbushCounterSection from '../../../../../side-peeks/character-inventory/inventory-item/partials/item-view/ambush-and-counter-section';
import AttackSection from '../../../../../side-peeks/character-inventory/inventory-item/partials/item-view/attack-section';
import DefenceSection from '../../../../../side-peeks/character-inventory/inventory-item/partials/item-view/defence-section';
import HealingSection from '../../../../../side-peeks/character-inventory/inventory-item/partials/item-view/healing-section';

const STAT_FIELDS = [
  { key: 'str_modifier' as const, label: 'Strength' },
  { key: 'dur_modifier' as const, label: 'Durability' },
  { key: 'dex_modifier' as const, label: 'Dexterity' },
  { key: 'chr_modifier' as const, label: 'Charisma' },
  { key: 'int_modifier' as const, label: 'Intelligence' },
  { key: 'agi_modifier' as const, label: 'Agility' },
  { key: 'focus_modifier' as const, label: 'Focus' },
] as const;

const SelectedCraftableItemDetails = ({
  item,
}: SelectedCraftableItemDetailsProps): ReactNode => {
  const itemColorClass = planeTextItemColors(
    item as unknown as BaseItemDetails
  );

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

  return (
    <div className="space-y-2">
      <p className={clsx('font-semibold', itemColorClass)}>{item.name}</p>
      {renderDescription()}
      <p className="text-xs text-gray-500 dark:text-gray-400">
        Type: {item.type} &bull; Cost: {item.cost.toLocaleString()} gold
      </p>
      <p className="text-xs text-gray-500 dark:text-gray-400">
        Skill Level Required: {item.skill_level_required} &bull; Trivial at:{' '}
        {item.skill_level_trivial}
      </p>
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
    </div>
  );
};

export default SelectedCraftableItemDetails;
