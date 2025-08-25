import React, { Fragment } from 'react';

import AdjustmentGroup from './adjustment-group';
import { AFFIX_ADJUSTMENT_FIELDS } from '../../../constants/item-comparison-constants';
import type AffixAdjustmentsSectionProps from '../../../types/partials/item-comparison/comparison-column-sections/affix-adjustment-section-props';
import { hasAnyNonZeroAdjustment } from '../../../utils/item-comparison';

import Separator from 'ui/separator/separator';

const AffixAdjustmentsSection = ({
  adjustments,
  show,
}: AffixAdjustmentsSectionProps) => {
  if (!show) {
    return null;
  }

  const hasAny = hasAnyNonZeroAdjustment(adjustments, AFFIX_ADJUSTMENT_FIELDS);

  if (!hasAny) {
    return null;
  }

  return (
    <Fragment>
      <h4 className="mt-3 mb-1 text-xs font-semibold uppercase tracking-wide text-mango-tango-500 dark:text-mango-tango-300">
        Affix Adjustments
      </h4>
      <Separator />
      <AdjustmentGroup
        adjustments={adjustments}
        fields={AFFIX_ADJUSTMENT_FIELDS}
      />
    </Fragment>
  );
};

export default AffixAdjustmentsSection;
