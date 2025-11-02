import React, { Fragment } from 'react';

import { HOLY_STACK_FIELDS } from '../../../constants/item-comparison-constants';
import type HolyStackSectionProps from '../../../types/partials/item-comparison/comparison-column-sections/holy-stack-section-props';
import AdjustmentGroup from '../adjustment-group';

import Separator from 'ui/separator/separator';

const HolyStackSection = ({ adjustments, show }: HolyStackSectionProps) => {
  if (!show) {
    return null;
  }

  return (
    <Fragment>
      <h4 className="text-mango-tango-500 dark:text-mango-tango-300 mt-3 mb-1 text-xs font-semibold tracking-wide uppercase">
        Holy Stacks
      </h4>
      <Separator />
      <AdjustmentGroup adjustments={adjustments} fields={HOLY_STACK_FIELDS} />
      <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
        <em>
          These effects are percentage-based stack adjustments that influence
          Devouring Darkness and stat-related bonuses during combat.
        </em>
      </p>
    </Fragment>
  );
};

export default HolyStackSection;
