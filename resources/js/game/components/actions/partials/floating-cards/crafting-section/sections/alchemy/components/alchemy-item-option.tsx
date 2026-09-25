import React, { ReactNode } from 'react';

import AlchemyItemOptionProps from './types/alchemy-item-option-props';
import CurrencyDisplay from '../../../../../../../../reusable-components/currency/currency-display';
import { CurrencyDisplayMode } from '../../../../../../../../reusable-components/currency/enums/currency-display-mode';
import { CurrencyType } from '../../../../../../../../reusable-components/currency/enums/currency-type';

import { formatNumberWithCommas } from 'game-utils/format-number';

const AlchemyItemOption = ({ item }: AlchemyItemOptionProps): ReactNode => (
  <span className="inline-flex flex-wrap items-center gap-x-2 gap-y-1">
    <span>{item.name}</span>
    <CurrencyDisplay
      currency={CurrencyType.GOLD_DUST}
      amount={item.gold_dust_cost}
      display_mode={CurrencyDisplayMode.EXACT}
    />
    <CurrencyDisplay
      currency={CurrencyType.SHARDS}
      amount={item.shards_cost}
      display_mode={CurrencyDisplayMode.EXACT}
    />
    <span>Owned: {formatNumberWithCommas(item.owned_amount)}</span>
  </span>
);

export default AlchemyItemOption;
