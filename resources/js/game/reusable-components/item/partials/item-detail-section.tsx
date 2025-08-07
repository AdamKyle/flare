import clsx from 'clsx';
import { isNil } from 'lodash';
import React from 'react';

import { formatNumberWithCommas } from '../../../util/format-number';
import ItemDetailSectionProps from '../types/partials/item-detail-section-props';
import { getItemInfoText } from '../utils/get-item-info-text';

import Dd from 'ui/dl/dd';
import Dt from 'ui/dl/dt';
import InfoToolTip from 'ui/tool-tips/info-tool-tip';

const ItemDetailSection = ({
  label,
  value,
  is_percent,
  item_type,
  is_adjustment,
}: ItemDetailSectionProps) => {
  if (isNil(value)) {
    return null;
  }

  const amount = is_percent
    ? `${(value * 100).toFixed(2)}%`
    : formatNumberWithCommas(value);

  const isCrafting = label.startsWith('Crafting');

  const displayValue = () => {
    if (is_adjustment) {
      return value < 0 ? `${amount}` : `+${amount}`;
    }

    return amount;
  };

  return (
    <React.Fragment key={label}>
      <Dt>
        <div className="flex items-center space-x-2">
          <InfoToolTip
            info_text={getItemInfoText(label, amount, item_type, value)}
          />
          <span
            className={clsx({
              'text-mango-tango-500 dark:text-mango-tango-300': isCrafting,
              'text-danube-600 dark:text-danube-300': !isCrafting,
            })}
          >
            {label}
          </span>
        </div>
      </Dt>
      <Dd>
        <div className="flex justify-end">
          <span
            className={clsx({
              'text-mango-tango-500 dark:text-mango-tango-300': isCrafting,
              'text-emerald-500 dark:text-emerald-300':
                !isCrafting && is_adjustment && value > 0,
              'text-rose-500 dark:text-rose-300':
                !isCrafting && is_adjustment && value < 0,
              'text-gray-800 dark:text-gray-300': !isCrafting && !is_adjustment,
            })}
          >
            {displayValue()}
          </span>
        </div>
      </Dd>
    </React.Fragment>
  );
};

export default ItemDetailSection;
