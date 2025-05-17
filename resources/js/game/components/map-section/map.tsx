import { isNil } from 'lodash';
import React, { useEffect, useState } from 'react';

import { MapApiUrls } from './api/enums/map-api-urls';
import { TimeOutDetails } from './api/hooks/definitions/base-map-api-definition';
import useBaseMapDetailsApi from './api/hooks/use-base-map-details-api';
import DraggableMap from './draggable-map';
import { MapIconPaths } from './enums/map-icon-paths';
import { useEmitCharacterPosition } from './hooks/use-emit-character-position';
import { useManageMovement } from './hooks/use-manage-movement';
import { useOpenCharacterKingdomInfoModal } from './hooks/use-open-character-kingdom-info-modal';
import { useOpenLocationInfoModal } from './hooks/use-open-location-info-modal';
import CharacterPin from './partials/character-pin';
import MapLocations from './partials/map-locations';
import MapTiles from './partials/map-tiles';
import CharacterMapPosition from './types/character-map-position';
import MapIcon from './types/map-icon';
import MapProps from './types/map-props';
import { useMovementTimer } from './websockets/hooks/use-movement-timer';
import { useFetchMovementTimeoutData } from '../actions/partials/floating-cards/map-section/hooks/use-fetch-movement-timeout-data';
import { useManageSetSailButtonState } from '../actions/partials/floating-cards/map-section/hooks/use-manage-set-sail-button-state';

import { GameDataError } from 'game-data/components/game-data-error';
import { useGameData } from 'game-data/hooks/use-game-data';

import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const Map = ({ additional_css, zoom = 1 }: MapProps) => {
  const [characterMapPosition, setCharacterMapPosition] =
    useState<CharacterMapPosition>({ x: 0, y: 0 });

  const { handleEventData } = useFetchMovementTimeoutData();

  const { manageSetSailButtonState } = useManageSetSailButtonState();

  const { emitCharacterPosition } = useEmitCharacterPosition();

  const { gameData } = useGameData();

  const characterData = gameData?.character;

  useManageMovement({
    setCharacterMapPosition,
    characterMapPosition,
    characterData,
  });

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

  const { openCharacterKingdomDetails } = useOpenCharacterKingdomInfoModal({
    characterData,
  });

  const { openLocationDetails } = useOpenLocationInfoModal({
    characterData,
  });

  useMovementTimer({
    characterData,
  });

  useEffect(() => {
    emitCharacterPosition(characterMapPosition);

    const foundPort = data?.locations.find((location) => {
      return (
        location.x_position === characterMapPosition.x &&
        location.y_position === characterMapPosition.y &&
        location.is_port
      );
    });

    if (!foundPort) {
      manageSetSailButtonState(false);

      return;
    }

    manageSetSailButtonState(true);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [characterMapPosition]);

  if (!characterData) {
    return <GameDataError />;
  }

  if (loading) {
    return <InfiniteLoader />;
  }

  if (isNil(data) || error) {
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

  const npcKingdoms: MapIcon[] = data.npc_kingdoms.map((kingdom) => {
    return {
      x: kingdom.x_position,
      y: kingdom.y_position,
      src: MapIconPaths.NPC_KINGDOM,
      alt: kingdom.name,
      id: kingdom.id,
    };
  });

  const enemyKingdoms: MapIcon[] = data.enemy_kingdoms.map((kingdom) => {
    return {
      x: kingdom.x_position,
      y: kingdom.y_position,
      src: MapIconPaths.ENEMY_KINGDOM,
      alt: kingdom.name,
      id: kingdom.id,
    };
  });

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
        character={characterPosition}
        zoom={zoom}
      >
        <MapTiles zoom={zoom} tiles={tiles} />
        <CharacterPin character={characterPosition} zoom={zoom} />
        <MapLocations
          zoom={zoom}
          mapIcons={characterKingdoms}
          onClick={handleCharacterKingdomClick}
        />
        <MapLocations
          zoom={zoom}
          mapIcons={enemyKingdoms}
          onClick={handleCharacterKingdomClick}
        />
        <MapLocations
          zoom={zoom}
          mapIcons={npcKingdoms}
          onClick={handleCharacterKingdomClick}
        />
        <MapLocations
          zoom={zoom}
          mapIcons={mapLocations}
          onClick={handleLocationClick}
        />
      </DraggableMap>
    </div>
  );
};

export default Map;
