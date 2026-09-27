import React, { ReactNode } from 'react';

import { CURRENCY_CACHE_TYPE_LABELS } from './enums/currency-cache-type-labels';
import CompensationCacheSectionProps from './types/compensation-cache-section-props';
import DefinitionRow from '../viewable-sections/definition-row';
import InfoLabel from '../viewable-sections/info-label';
import Section from '../viewable-sections/section';

import { formatNumberWithCommas } from 'game-utils/format-number';

const CompensationCacheSection = ({
  currency_cache_type: currencyCacheType,
  cache_amount: cacheAmount,
  show_separator: showSeparator,
}: CompensationCacheSectionProps): ReactNode => {
  return (
    <Section title="Compensation Cache" showSeparator={showSeparator}>
      <DefinitionRow
        left={<InfoLabel label="Currency" />}
        right={
          <span className="text-ferra-800 dark:text-ferra-300">
            {CURRENCY_CACHE_TYPE_LABELS[currencyCacheType]}
          </span>
        }
      />
      <DefinitionRow
        left={<InfoLabel label="Remaining" />}
        right={
          <span className="text-ferra-800 dark:text-ferra-300">
            {formatNumberWithCommas(cacheAmount)}
          </span>
        }
      />
    </Section>
  );
};

export default CompensationCacheSection;
