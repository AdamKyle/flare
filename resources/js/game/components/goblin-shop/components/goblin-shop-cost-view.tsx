import React from 'react';

import DefinitionRow from '../../../reusable-components/viewable-sections/definition-row';
import InfoLabel from '../../../reusable-components/viewable-sections/info-label';
import Section from '../../../reusable-components/viewable-sections/section';
import { formatNumberWithCommas } from '../../../util/format-number';
import GoblinShopCostViewProps from '../types/goblin-shop-cost-view-props';

const GoblinShopCostView = ({ item }: GoblinShopCostViewProps) => {
  return (
    <Section title="Cost" showSeparator={false} showTitleSeparator={true}>
      <DefinitionRow
        left={<InfoLabel label="Gold Bars" />}
        right={
          <span className="font-medium">
            {formatNumberWithCommas(item.gold_bars_cost ?? 0)}
          </span>
        }
      />
    </Section>
  );
};

export default GoblinShopCostView;
