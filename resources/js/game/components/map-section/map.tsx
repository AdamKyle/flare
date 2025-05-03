import { isNil } from 'lodash';
import React, { useEffect, useState } from 'react';

import { MapApiUrls } from './api/enums/map-api-urls';
import useBaseMapDetailsApi from './api/hooks/use-base-map-details-api';
import { useMoveCharacterDirectionallyApi } from './api/hooks/use-move-character-directionally-api';
import DraggableMap from './draggable-map';
import { MapIconPaths } from './enums/map-icon-paths';
import { useOpenCharacterKingdomInfoModal } from './hooks/use-open-character-kingdom-info-modal';
import { useProcessDirectionalMovement } from './hooks/use-process-directional-movement';
import CharacterMapPosition from './types/character-map-position';
import MapIcon from './types/map-icon';
import MapProps from './types/map-props';
import { useDirectionallyMoveCharacter } from '../actions/partials/floating-cards/map-section/hooks/use-directionally-move-character';

import { GameDataError } from 'game-data/components/game-data-error';
import { useGameData } from 'game-data/hooks/use-game-data';

import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const Map = ({ additional_css, zoom }: MapProps) => {
  const [characterMapPosition, setCharacterMapPosition] =
    useState<CharacterMapPosition>({ x: 0, y: 0 });

  const { movementAmount, movementType, resetMovementAmount } =
    useDirectionallyMoveCharacter();

  const { gameData } = useGameData();

  const characterData = gameData?.character;

  const { loading, error, data } = useBaseMapDetailsApi({
    url: MapApiUrls.BASE_MAP_DETAILS,
    callback: setCharacterMapPosition,
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

  const handleMapClick = (icon: MapIcon) => {
    openCharacterKingdomDetails(icon.id);
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

  return (
    <div className={additional_css}>
      <DraggableMap
        additional_css={'w-full h-full'}
        tiles={tiles}
        map_icons={characterKingdoms}
        character={characterPosition}
        on_click={handleMapClick}
        zoom={zoom}
      />
    </div>
  );
};

export default Map;
