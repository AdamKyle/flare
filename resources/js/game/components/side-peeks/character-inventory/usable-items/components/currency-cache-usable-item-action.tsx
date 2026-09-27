import React, { ReactNode } from 'react';

import CurrencyCacheUsableItemActionProps from './types/currency-cache-usable-item-action-props';
import { CURRENCY_CACHE_TYPE_LABELS } from '../../../../../reusable-components/usable-item/enums/currency-cache-type-labels';

import { formatNumberWithCommas } from 'game-utils/format-number';

import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import LoadingButton from 'ui/buttons/loading-button';

const CurrencyCacheUsableItemAction = ({
  currency_cache_type: currencyCacheType,
  cache_amount: cacheAmount,
  is_using: isUsing,
  on_use: onUse,
}: CurrencyCacheUsableItemActionProps): ReactNode => {
  const currencyLabel = CURRENCY_CACHE_TYPE_LABELS[currencyCacheType];

  return (
    <div className="border-ferra-300 bg-ferra-50 text-ferra-900 dark:border-ferra-700 dark:bg-ferra-950 dark:text-ferra-100 flex flex-col gap-2 rounded-md border p-3">
      <div className="flex flex-wrap items-center justify-between gap-2 text-sm">
        <span>
          <strong>Currency</strong>: {currencyLabel}
        </span>
        <span>
          <strong>Remaining</strong>: {formatNumberWithCommas(cacheAmount)}
        </span>
      </div>
      <LoadingButton
        label="Use Cache"
        loading_label="Using Cache..."
        variant={ButtonVariant.ALCHEMY}
        is_loading={isUsing}
        on_click={onUse}
      />
    </div>
  );
};

export default CurrencyCacheUsableItemAction;
