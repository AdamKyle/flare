import React, { Fragment } from 'react';

import { DEVOURING_FIELDS } from '../../../constants/item-comparison-constants';
import DevouringSectionProps from '../../../types/partials/item-comparison/comparison-column-sections/devouring-section-props';
import AdjustmentGroup from '../adjustment-group';

import Separator from 'ui/separator/separator';

const DevouringSection = ({ adjustments, show }: DevouringSectionProps) => {
  if (!show) return null;

  return (
    <Fragment>
      <h4 className="mt-3 mb-1 text-xs font-semibold uppercase tracking-wide text-mango-tango-500 dark:text-mango-tango-300">
        Devouring Adjustments
      </h4>
      <Separator />
      <AdjustmentGroup adjustments={adjustments} fields={DEVOURING_FIELDS} />
      <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
        <em>
          Devouring Darkness voids the enemies&apos; chance to void your
          enchantments, while Devouring Light voids the enemies&apos; special
          enchantments. This can turn the battle in your favour.
        </em>
      </p>
    </Fragment>
  );
};

export default DevouringSection;
