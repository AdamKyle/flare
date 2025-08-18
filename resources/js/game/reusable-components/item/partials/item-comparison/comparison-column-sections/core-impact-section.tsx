import React, { Fragment } from 'react';

import { TOP_FIELDS } from '../../../constants/item-comparison-constants';
import CoreImpactSectionProps from '../../../types/partials/item-comparison/comparison-column-sections/core-impact-section-props';
import AdjustmentGroup from '../adjustment-group';

import Separator from 'ui/separator/separator';

const CoreImpactSection = ({
  adjustments,
  hasCoreTotals,
  showAdvancedChildUnderTop,
  forceCoreZeroKeys = [],
}: CoreImpactSectionProps) => {
  const shouldShow = hasCoreTotals || forceCoreZeroKeys.length > 0;
  if (!shouldShow) return null;

  return (
    <Fragment>
      <h4 className="mt-2 mb-1 text-xs font-semibold uppercase tracking-wide text-mango-tango-500 dark:text-mango-tango-300">
        Core Impact
      </h4>
      <Separator />
      <AdjustmentGroup
        adjustments={adjustments}
        fields={TOP_FIELDS}
        showAdvancedChild={showAdvancedChildUnderTop}
        forceShowZeroKeys={forceCoreZeroKeys}
      />
    </Fragment>
  );
};

export default CoreImpactSection;
