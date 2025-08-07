import React from 'react';

import BaseSectionProps from '../../types/partials/item-detail-sections/base-section-props';
import { getItemStats } from '../../utils/item-stats';
import ItemDetailSection from '../item-detail-section';

import Dl from 'ui/dl/dl';
import Separator from 'ui/separator/separator';

const EnemyStatsReduction = ({ item, is_adjustment }: BaseSectionProps) => {
  return (
    <div>
      <h4 className="mb-2 text-sm font-semibold text-danube-600 dark:text-danube-300">
        Stats
      </h4>
      <Separator />
      <Dl>
        {getItemStats(item, is_adjustment).map((entry, index) => (
          <ItemDetailSection
            item_type={item.type}
            label={entry.label}
            value={entry.value}
            is_percent={entry.isPercent}
            key={`${item.id}-${index}`}
            is_adjustment={is_adjustment}
          />
        ))}
      </Dl>
    </div>
  );
};

export default EnemyStatsReduction;
