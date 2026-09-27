import React, { ReactNode } from 'react';

import SignedAdjustment from '../../item/partials/signed-adjustment';
import PositiveDetailRowProps from '../types/positive-detail-row-props';

import Dd from 'ui/dl/dd';
import Dt from 'ui/dl/dt';

const PositiveDetailRow = ({
  label,
  value,
}: PositiveDetailRowProps): ReactNode => {
  if (value === null || value === undefined || value <= 0) {
    return null;
  }

  return (
    <>
      <Dt>{label}</Dt>
      <Dd>
        <SignedAdjustment
          value={value}
          display_text={`+${value}`}
          screen_reader_text={`${label}: positive ${value}`}
        />
      </Dd>
    </>
  );
};

export default PositiveDetailRow;
