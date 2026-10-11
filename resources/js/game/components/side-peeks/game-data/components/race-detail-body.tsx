import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode } from 'react';

import RaceDetailBodyProps from './types/race-detail-body-props';
import { usePlayerRaceDetail } from '../../../../reusable-components/race/api/hooks/use-player-race-detail';
import RaceDetail from '../../../../reusable-components/race/components/race-detail';

import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const RaceDetailBody = ({
  race_id: raceId,
}: RaceDetailBodyProps): ReactNode => {
  const { race, loading, error } = usePlayerRaceDetail(raceId);

  if (loading) {
    return <InfiniteLoader />;
  }

  if (error || !race) {
    return (
      <ApiErrorAlert apiError={error?.message ?? 'Unable to load this Race.'} />
    );
  }

  return <RaceDetail race={race} />;
};

export default RaceDetailBody;
