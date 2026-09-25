import React, { ReactNode } from 'react';

import EnchantingAffixOptionProps from './types/enchanting-affix-option-props';
import CurrencyDisplay from '../../../../../../../../reusable-components/currency/currency-display';
import { CurrencyDisplayMode } from '../../../../../../../../reusable-components/currency/enums/currency-display-mode';
import { CurrencyType } from '../../../../../../../../reusable-components/currency/enums/currency-type';

const EnchantingAffixOption = ({
  option,
  affixes,
}: EnchantingAffixOptionProps): ReactNode => {
  const affix = affixes.find((loadedAffix) => loadedAffix.id === option.value);

  if (!affix) {
    return option.label;
  }

  return (
    <span className="inline-flex flex-wrap items-center gap-x-2 gap-y-1">
      <span>{affix.name}</span>
      <CurrencyDisplay
        currency={CurrencyType.GOLD}
        amount={affix.cost}
        display_mode={CurrencyDisplayMode.EXACT}
      />
      <span>INT REQ: {affix.int_required}</span>
    </span>
  );
};

export default EnchantingAffixOption;
