import ApiErrorAlert from 'api-handler/components/api-error-alert';
import { isNil } from 'lodash';
import React, { useEffect, useState } from 'react';

import LocationDetailsProps from './types/location-details-props';
import TeleportSection from '../../components/map-actions/teleport-section';
import { LocationApiUrls } from '../api/enums/location-api-urls';
import { useFetchLocationDetailsApi } from '../api/hooks/use-fetch-location-details-api';
import { TeleportApiUrls } from '../teleport/api/enums/teleport-api-urls';
import { useTeleportPlayerApi } from '../teleport/api/hooks/use-teleport-player-api';
import { calculateCostOfTeleport } from '../util/calculate-cost-of-teleport';

import { GameDataError } from 'game-data/components/game-data-error';

import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const LocationDetails = ({
  location_id,
  character_x,
  character_y,
  character_gold,
  character_id,
}: LocationDetailsProps) => {
  const { data, error, loading } = useFetchLocationDetailsApi({
    location_id,
    url: LocationApiUrls.LOCATION_INFO,
  });

  const { setRequestParams } = useTeleportPlayerApi({
    url: TeleportApiUrls.TELEPORT_PLAYER,
    character_id,
  });

  const [costOfTeleport, setCostOfTeleport] = useState(0);
  const [timeOutValue, setTimeOutvalue] = useState(0);
  const [canAffordToTeleport, setCanAffordToTeleport] = useState(true);

  useEffect(
    () => {
      if (isNil(data)) {
        return;
      }

      const calculatedCostOfTeleport = calculateCostOfTeleport({
        character_position: {
          x_position: character_x,
          y_position: character_y,
        },
        new_character_position: {
          x_position: data.x,
          y_position: data.y,
        },
        character_gold: character_gold,
      });

      setCostOfTeleport(calculatedCostOfTeleport.cost);
      setTimeOutvalue(calculatedCostOfTeleport.time);
      setCanAffordToTeleport(calculatedCostOfTeleport.can_afford);
    },
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [data]
  );

  const handleTeleport = () => {
    if (isNil(data)) {
      return;
    }

    setRequestParams({
      x: data.x,
      y: data.y,
      cost: costOfTeleport,
      timeout: timeOutValue,
    });
  };

  if (loading) {
    return <InfiniteLoader />;
  }

  if (isNil(data)) {
    return <GameDataError />;
  }

  if (!isNil(error)) {
    return <ApiErrorAlert apiError={error.message} />;
  }

  return (
    <div className={'p-2'}>
      <p className={'text-gray-800 dark:text-gray-300'}>{data.description}</p>
      <div className={'my-4'}>
        <TeleportSection
          character_gold={character_gold}
          cost_of_teleport={costOfTeleport}
          on_teleport={handleTeleport}
          can_afford_to_teleport={canAffordToTeleport}
          time_out_value={timeOutValue}
        />
      </div>

    </div>
  );
};

export default LocationDetails;
