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
}: ItemDetailSectionProps) => {
  if (isNil(value) || value <= 0) {
    return null;
  }

  const amount = is_percent
    ? `${(value * 100).toFixed(2)}%`
    : formatNumberWithCommas(value);

  const isCrafting = label.startsWith('Crafting');

  const display = `+${amount}`;

  return (
    <React.Fragment key={label}>
      <Dt>
        <div className="flex items-center space-x-2">
          <InfoToolTip info_text={getItemInfoText(label, amount, item_type)} />
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
              'text-emerald-500 dark:text-emerald-300': !isCrafting,
            })}
          >
            {display}
          </span>
        </div>
      </Dd>
    </React.Fragment>
  );
};

export default ItemDetailSection;
