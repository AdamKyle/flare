import React, { ReactNode } from 'react';

import RaceDetailBody from './components/race-detail-body';
import RaceDetailSidePeekProps from './types/race-detail-side-peek-props';

const RaceDetailSidePeek = ({
  race_id: raceId,
}: RaceDetailSidePeekProps): ReactNode => {
  return (
    <div className="px-4 py-4 sm:px-5">
      <RaceDetailBody race_id={raceId} />
    </div>
  );
};

export default RaceDetailSidePeek;
