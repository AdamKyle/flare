// components/shop/partials/StatsBlock.tsx
import React from 'react';

import StatsAttributesBlockRow from './stat-attributes-block-row';
import { EquippableItemWithBase } from '../../../../api-definitions/items/equippable-item-definitions/base-equippable-item-definition';

import Dl from 'ui/dl/dl';
import Separator from 'ui/separator/separator';

type StatsBlockProps = {
  item: EquippableItemWithBase;
};

const StatsAttributesBlock = ({ item }: StatsBlockProps) => {
  return (
    <div>
      <h3 className="mb-1 text-sm font-semibold uppercase tracking-wide text-mango-tango-500 dark:text-mango-tango-300">
        Stats
      </h3>
      <Separator />
      <Dl>
        <StatsAttributesBlockRow
          label="Strength"
          value={Number(item.str_modifier ?? 0)}
        />
        <StatsAttributesBlockRow
          label="Intelligence"
          value={Number(item.int_modifier ?? 0)}
        />
        <StatsAttributesBlockRow
          label="Dexterity"
          value={Number(item.dex_modifier ?? 0)}
        />
        <StatsAttributesBlockRow
          label="Focus"
          value={Number(item.focus_modifier ?? 0)}
        />
        <StatsAttributesBlockRow
          label="Charisma"
          value={Number(item.chr_modifier ?? 0)}
        />
        <StatsAttributesBlockRow
          label="Agility"
          value={Number(item.agi_modifier ?? 0)}
        />
        <StatsAttributesBlockRow
          label="Durability"
          value={Number(item.dur_modifier ?? 0)}
        />
      </Dl>
    </div>
  );
};

export default StatsAttributesBlock;
