import React, { ReactNode } from 'react';

import TrinketOptionProps from './types/trinket-option-props';
import CurrencyDisplay from '../../../../../../../../reusable-components/currency/currency-display';
import { CurrencyDisplayMode } from '../../../../../../../../reusable-components/currency/enums/currency-display-mode';
import { CurrencyType } from '../../../../../../../../reusable-components/currency/enums/currency-type';

const TrinketOption = ({ item }: TrinketOptionProps): ReactNode => (
  <span className="inline-flex flex-wrap items-center gap-x-2 gap-y-1">
    <span>{item.preview.name}</span>
    <CurrencyDisplay
      currency={CurrencyType.GOLD_DUST}
      amount={item.gold_dust_cost}
      display_mode={CurrencyDisplayMode.EXACT}
    />
    <CurrencyDisplay
      currency={CurrencyType.COPPER_COINS}
      amount={item.copper_coin_cost}
      display_mode={CurrencyDisplayMode.EXACT}
    />
  </span>
);

export default TrinketOption;
