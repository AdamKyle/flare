import React, { ReactNode } from 'react';

import SpecialUsableItemGuidanceProps from './types/special-usable-item-guidance-props';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';

const SpecialUsableItemGuidance = ({
  item,
}: SpecialUsableItemGuidanceProps): ReactNode => {
  if (item.holy_level !== null) {
    return (
      <Alert variant={AlertVariant.INFO}>
        Holy Oils are applied through Craft → Work Bench.
      </Alert>
    );
  }

  if (item.damages_kingdoms === true) {
    return (
      <Alert variant={AlertVariant.INFO}>
        This Alchemy item is used against Kingdoms through the Kingdom attack
        flow.
      </Alert>
    );
  }

  return null;
};

export default SpecialUsableItemGuidance;
