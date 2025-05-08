import { isNil } from 'lodash';
import React, { useEffect, useState } from 'react';

import { MapApiUrls } from './api/enums/map-api-urls';
import { TimeOutDetails } from './api/hooks/definitions/base-map-api-definition';
import useBaseMapDetailsApi from './api/hooks/use-base-map-details-api';
import { useMoveCharacterDirectionallyApi } from './api/hooks/use-move-character-directionally-api';
import DraggableMap from './draggable-map';
import { MapIconPaths } from './enums/map-icon-paths';
import { useOpenCharacterKingdomInfoModal } from './hooks/use-open-character-kingdom-info-modal';
import { useOpenLocationInfoModal } from './hooks/use-open-location-info-modal';
import { useProcessDirectionalMovement } from './hooks/use-process-directional-movement';
import CharacterMapPosition from './types/character-map-position';
import MapIcon from './types/map-icon';
import MapProps from './types/map-props';
import { useMovementTimer } from './websockets/hooks/use-movement-timer';
import { useDirectionallyMoveCharacter } from '../actions/partials/floating-cards/map-section/hooks/use-directionally-move-character';
import { useFetchMovementTimeoutData } from '../actions/partials/floating-cards/map-section/hooks/use-fetch-movement-timeout-data';

import { GameDataError } from 'game-data/components/game-data-error';
import { useGameData } from 'game-data/hooks/use-game-data';

import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const Map = ({ additional_css, zoom }: MapProps) => {
  const [characterMapPosition, setCharacterMapPosition] =
    useState<CharacterMapPosition>({ x: 0, y: 0 });

  const { movementAmount, movementType, resetMovementAmount } =
    useDirectionallyMoveCharacter();

  const { handleEventData } = useFetchMovementTimeoutData();

  const { gameData } = useGameData();

  const characterData = gameData?.character;

  const mapDetailsHandler = (
    characterMapPosition: CharacterMapPosition,
    timeoutDetails: TimeOutDetails
  ) => {
    handleEventData({
      canMove: timeoutDetails.can_move,
      activateBar: timeoutDetails.show_timer,
      forLength: timeoutDetails.time_left,
    });

    setCharacterMapPosition({
      x: characterMapPosition.x,
      y: characterMapPosition.y,
    });
  };

  const { loading, error, data } = useBaseMapDetailsApi({
    url: MapApiUrls.BASE_MAP_DETAILS,
    callback: mapDetailsHandler,
    characterData,
  });

  const { setRequestParams, error: movementError } =
    useMoveCharacterDirectionallyApi({
      url: MapApiUrls.MOVE_CHARACTER_DIRECTIONALLY,
      callback: setCharacterMapPosition,
      handleResetMapMovement: resetMovementAmount,
      characterData,
    });

  const { setUpdateCharacterPosition, updatePosition } =
    useProcessDirectionalMovement({
      onPositionChange: setRequestParams,
      onCharacterPositionChange: setCharacterMapPosition,
    });

  const { openCharacterKingdomDetails } = useOpenCharacterKingdomInfoModal({
    characterData,
  });

  const { openLocationDetails } = useOpenLocationInfoModal({
    characterData,
  });

  useMovementTimer({
    characterData,
  });

  useEffect(
    () => {
      if (!movementType) {
        return;
      }

      updatePosition({
        baseX: characterMapPosition.x,
        baseY: characterMapPosition.y,
        movementAmount,
        movementType,
      });

      setUpdateCharacterPosition((prevValue) => !prevValue);
    },
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [movementAmount, movementType]
  );

  if (!characterData) {
    return <GameDataError />;
  }

  if (loading) {
    return <InfiniteLoader />;
  }

  if (isNil(data) || error || movementError) {
    return <GameDataError />;
  }

  const handleCharacterKingdomClick = (icon: MapIcon) => {
    const foundKingdom = data.character_kingdoms.find(
      (kingdom) => kingdom.id === icon.id
    );

    if (!foundKingdom) {
      return;
    }

    openCharacterKingdomDetails(icon.id, foundKingdom.name);
  };

  const handleLocationClick = (icon: MapIcon) => {
    const foundLocation = data.locations.find(
      (location) => location.id === icon.id
    );

    if (!foundLocation) {
      return;
    }

    openLocationDetails(icon.id, foundLocation.name);
  };

  const tiles = data.tiles;

  const characterKingdoms: MapIcon[] = data.character_kingdoms.map(
    (kingdom) => {
      return {
        x: kingdom.x_position,
        y: kingdom.y_position,
        src: MapIconPaths.PLAYER_KINGDOM,
        alt: kingdom.name,
        is_character_kingdom: true,
        id: kingdom.id,
      };
    }
  );

  const characterPosition: MapIcon = {
    x: characterMapPosition.x,
    y: characterMapPosition.y,
    src: MapIconPaths.CHARACTER,
    alt: characterData.name,
    id: characterData.id,
  };

  const mapLocations: MapIcon[] = data.locations.map((location) => {
    let mapIconSrc: string = MapIconPaths.LOCATION;

    if (location.is_port) {
      mapIconSrc = MapIconPaths.PORT_LOCATION;
    }

    if (location.is_corrupted) {
      mapIconSrc = MapIconPaths.CORRUPTED_LOCATION;
    }

    return {
      x: location.x_position,
      y: location.y_position,
      src: mapIconSrc,
      alt: location.name,
      id: location.id,
    };
  });

  return (
    <div className={additional_css}>
      <DraggableMap
        additional_css={'w-full h-full'}
        tiles={tiles}
        character_kingdom_icons={characterKingdoms}
        location_icons={mapLocations}
        character={characterPosition}
        on_character_kingdom_click={handleCharacterKingdomClick}
        on_location_click={handleLocationClick}
        zoom={zoom}
      />
    </div>
  );
};

export default Map;
