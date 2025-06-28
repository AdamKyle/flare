import ApiErrorAlert from 'api-handler/components/api-error-alert';
import { isNil } from 'lodash';
import React, { useEffect, useState } from 'react';
import { match } from 'ts-pattern';

import LocationDroppableItems from './location-droppable-items';
import CorruptedLocationDetails from './partials/corrupted-location';
import CorruptedLocationInfo from './partials/corrupted-location-info.tsx';
import LocationDetailsProps from './types/location-details-props';
import TeleportSection from '../../components/map-actions/teleport-section';
import { LocationApiUrls } from '../api/enums/location-api-urls';
import { useFetchLocationDetailsApi } from '../api/hooks/use-fetch-location-details-api';
import { TeleportApiUrls } from '../teleport/api/enums/teleport-api-urls';
import { useTeleportPlayerApi } from '../teleport/api/hooks/use-teleport-player-api';
import { calculateCostOfTeleport } from '../util/calculate-cost-of-teleport';
import { LocationInfoTypes } from './enums/location-info-types';
import EnemyStrengthIncrease from './partials/enemy-strength-increase';
import EnemyStrengthIncreaseInfo from './partials/enemy-strength-increase-info';

import { GameDataError } from 'game-data/components/game-data-error';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Dl from 'ui/dl/dl';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';
import Separator from 'ui/seperatror/separator';

const LocationDetails = ({
  location_id,
  character_x,
  character_y,
  character_gold,
  character_id,
  show_title,
  on_close,
}: LocationDetailsProps) => {
  const [locationInfoType, setLocationInfoType] =
    useState<LocationInfoTypes | null>(null);

  const [showQuestItems, setShowQuestItems] = useState(false);

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

  const handleOpenInfo = (type: LocationInfoTypes) => {
    setLocationInfoType(type);
  };

  const openItemsList = () => {
    setShowQuestItems(true);
  };

  const handelCloseInfo = () => {
    setLocationInfoType(null);
  };

  const handleGoBack = () => {
    setShowQuestItems(false);
  };

  const handleCloseLocationDetails = () => {
    if (!on_close) {
      return;
    }

    on_close();
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

  if (showQuestItems) {
    return (
      <LocationDroppableItems
        location_id={location_id}
        go_back={handleGoBack}
      />
    );
  }

  if (!isNil(locationInfoType)) {
    return (
      match(locationInfoType)
        .with(LocationInfoTypes.ENEMY_STRENGTH_INCREASE, () => (
          <EnemyStrengthIncreaseInfo
            handel_close_info_section={handelCloseInfo}
          />
        ))
        .with(LocationInfoTypes.CORRUPTED, () => (
          <CorruptedLocationInfo handel_close_info_section={handelCloseInfo} />
        ))
        // If you’re certain you’ve handled every enum member:
        .exhaustive()
    );
  }

  const renderViewDroppableItems = () => {
    if (isNil(data.enemy_strength_increase)) {
      return null;
    }

    return (
      <>
        <Separator />
        <div className={'prose dark:prose-dark dark:text-white'}>
          <h3>Droppable Items</h3>
          <p>
            This location drops quest items when you are manually fighting
            monsters here. Below you can open the list of quest items that can
            drop.
          </p>
          <Button
            on_click={openItemsList}
            label={'Obtainable Quest Items'}
            variant={ButtonVariant.PRIMARY}
            additional_css={'w-full'}
          />
        </div>
      </>
    );
  };

  const renderTitle = () => {
    if (!show_title) {
      return;
    }

    return (
      <>
        <h2 className={'text-gray-800 dark:text-gray-300'}>{data.name}</h2>
        <Separator />
        <Button
          on_click={handleCloseLocationDetails}
          label={'Go Back'}
          variant={ButtonVariant.PRIMARY}
          additional_css={'w-full'}
        />
        <Separator />
      </>
    );
  };

  return (
    <div className="px-4 py-2">
      {renderTitle()}
      <p className="text-gray-800 dark:text-gray-300">{data.description}</p>

      <div className="my-4">
        <TeleportSection
          character_gold={character_gold}
          cost_of_teleport={costOfTeleport}
          on_teleport={handleTeleport}
          can_afford_to_teleport={canAffordToTeleport}
          time_out_value={timeOutValue}
        />
      </div>

      <Separator />

      <Dl>
        <EnemyStrengthIncrease
          enemy_strength_increase={data.enemy_strength_increase}
          handle_on_info_click={handleOpenInfo}
        />
        <CorruptedLocationDetails
          is_corrupted={data.is_corrupted}
          handle_on_info_click={handleOpenInfo}
        />
      </Dl>

      {renderViewDroppableItems()}
    </div>
  );
};

export default LocationDetails;
