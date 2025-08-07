import React from 'react';

import CounterAndAmbushSection from './item-detail-sections/counter-and-ambush-section';
import { ItemDetailsSectionLabels } from '../enums/item-details-section-labels';
import EnemyStatsReduction from './item-detail-sections/enemy-stats-reduction';
import SkillSection from './item-detail-sections/skill-section';
import ExpandedItemComparisonProps from '../types/partials/expanded-item-comparison-props';

import Separator from 'ui/separator/separator';

const ExpandedItemComparison = ({
  expandedDetails,
}: ExpandedItemComparisonProps) => {
  return (
    <>
      <CounterAndAmbushSection
        item={expandedDetails}
        attributes={[
          {
            label: ItemDetailsSectionLabels.COUNTER,
            attribute: 'counter_chance_adjustment',
          },
          {
            label: ItemDetailsSectionLabels.COUNTER_RESISTANCE,
            attribute: 'counter_resistance_adjustment',
          },
          {
            label: ItemDetailsSectionLabels.AMBUSH,
            attribute: 'ambush_chance_adjustment',
          },
          {
            label: ItemDetailsSectionLabels.AMBUSH_RESISTANCE,
            attribute: 'ambush_resistance_adjustment',
          },
        ]}
        is_adjustment
      />
      <Separator />
      <SkillSection item={expandedDetails} is_adjustment />
      <Separator />
      <EnemyStatsReduction item={expandedDetails} />
    </>
  );
};

export default ExpandedItemComparison;
