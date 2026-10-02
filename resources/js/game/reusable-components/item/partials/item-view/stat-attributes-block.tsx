import React from 'react';

import StatsAttributesBlockRow from './stat-attributes-block-row';
import { useGuideTarget } from '../../../../components/guide-quests/walkthrough/hooks/use-guide-target';
import { GuideTargets } from '../../../../components/guide-quests/walkthrough/registry/guide-targets';
import StatAttributesBlockProps from '../../types/partials/item-view/stat-attributes-block-props';

import Dl from 'ui/dl/dl';
import Separator from 'ui/separator/separator';

const StatsAttributesBlock = ({ item }: StatAttributesBlockProps) => {
  const itemStatsGuideTarget = useGuideTarget(GuideTargets.ITEM_STATS);

  return (
    <div {...itemStatsGuideTarget}>
      <h3 className="text-mango-tango-500 dark:text-mango-tango-300 mb-1 text-sm font-semibold tracking-wide uppercase">
        Stats
      </h3>
      <Separator />
      <Dl>
        <StatsAttributesBlockRow
          label="Strength"
          value={item.str_modifier ?? 0}
        />
        <StatsAttributesBlockRow
          label="Intelligence"
          value={item.int_modifier ?? 0}
        />
        <StatsAttributesBlockRow
          label="Dexterity"
          value={item.dex_modifier ?? 0}
        />
        <StatsAttributesBlockRow
          label="Focus"
          value={item.focus_modifier ?? 0}
        />
        <StatsAttributesBlockRow
          label="Charisma"
          value={item.chr_modifier ?? 0}
        />
        <StatsAttributesBlockRow
          label="Agility"
          value={item.agi_modifier ?? 0}
        />
        <StatsAttributesBlockRow
          label="Durability"
          value={item.dur_modifier ?? 0}
        />
      </Dl>
    </div>
  );
};

export default StatsAttributesBlock;
