import React from 'react';

import ItemDetailSectionProps from '../../types/partials/item-detail-sections/stats-section-props';
import { getBaseItemStats } from '../../utils/item-stats';
import ItemDetailSection from '../item-detail-section';

import Dl from 'ui/dl/dl';
import Separator from 'ui/separator/separator';

const StatsSection = ({ item }: ItemDetailSectionProps) => {
  return (
    <div>
      <h4 className="mb-2 text-sm font-semibold text-danube-600 dark:text-danube-300">
        Stats
      </h4>
      <Separator />
      <Dl>
        {getBaseItemStats(item).map((entry, index) => (
          <ItemDetailSection
            item_type={item.type}
            label={entry.label}
            value={entry.value}
            is_percent={entry.isPercent}
            key={`${item.id}-${index}`}
          />
        ))}
      </Dl>
    </div>
  );
};

export default StatsSection;
