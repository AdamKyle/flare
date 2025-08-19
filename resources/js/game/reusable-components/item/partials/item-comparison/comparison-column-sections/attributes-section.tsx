import React, { Fragment } from 'react';

import { STAT_FIELDS } from '../../../constants/item-comparison-constants';
import AttributesSectionProps from '../../../types/partials/item-comparison/comparison-column-sections/attributes-section-props';
import AdjustmentGroup from '../adjustment-group';

import Separator from 'ui/separator/separator';

const AttributesSection = ({
  adjustments,
  hasAttributes,
}: AttributesSectionProps) => {
  if (!hasAttributes) {
    return null;
  }

  return (
    <Fragment>
      <h4 className="mt-4 mb-1 text-xs font-semibold uppercase tracking-wide text-mango-tango-500 dark:text-mango-tango-300">
        Attributes
      </h4>
      <Separator />
      <AdjustmentGroup adjustments={adjustments} fields={STAT_FIELDS} />
    </Fragment>
  );
};

export default AttributesSection;
