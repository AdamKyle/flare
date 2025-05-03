import { isNil } from 'lodash';
import React, { useRef, useState } from 'react';

import { MapApiUrls } from './api/enums/map-api-urls';
import useBaseMapDetailsApi from './api/hooks/use-base-map-details-api';
import DraggableMap from './draggable-map';
import { MapIconPaths } from './enums/map-icon-paths';
import { useOpenCharacterKingdomInfoModal } from './hooks/use-open-character-kingdom-info-modal';
import MapIcon from './types/map-icon';
import MapProps from './types/map-props';
import { useDirectionallyMoveCharacter } from '../actions/partials/floating-cards/map-section/hooks/use-directionally-move-character';
import { MapMovementTypes } from '../actions/partials/floating-cards/map-section/map-movement-types/map-movement-types';

import { GameDataError } from 'game-data/components/game-data-error';
import { useGameData } from 'game-data/hooks/use-game-data';

import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const getNextPosition = (
  baseX: number,
  baseY: number,
  movementAmount: number,
  movementType: MapMovementTypes | null
): { x: number; y: number } => {
  if (!movementType) {
    return { x: baseX, y: baseY };
  }

  if (movementType === MapMovementTypes.EAST) {
    baseX += movementAmount;
  }
  if (movementType === MapMovementTypes.WEST) {
    baseX += movementAmount;
  }
  if (movementType === MapMovementTypes.NORTH) {
    baseY += movementAmount;
  }
  if (movementType === MapMovementTypes.SOUTH) {
    baseY += movementAmount;
  }

  return { x: baseX, y: baseY };
};

const Map = ({ additional_css, zoom }: MapProps) => {
  const { movementAmount, movementType } = useDirectionallyMoveCharacter();

  const containerRef = useRef<HTMLDivElement>(null);

  const [characterMapPosition, setCharacterMapPosition] = useState<{
    x: number;
    y: number;
  }>({ x: 0, y: 0 });

  const { gameData } = useGameData();

  const characterData = gameData?.character;

  const { loading, error, data } = useBaseMapDetailsApi({
    url: MapApiUrls.BASE_MAP_DETAILS,
    characterData: characterData,
    callback: setCharacterMapPosition,
  });

  if (!characterData) {
    return <GameDataError />;
  }

  const { openCharacterKingdomDetails } = useOpenCharacterKingdomInfoModal({
    character_id: characterData.id,
  });

  if (loading) {
    return <InfiniteLoader />;
  }

  if (isNil(data) || error) {
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

  const newCharacterPosition = getNextPosition(
    characterMapPosition.x,
    characterMapPosition.y,
    movementAmount,
    movementType
  );

  const characterPosition: MapIcon = {
    x: newCharacterPosition.x,
    y: newCharacterPosition.y,
    src: MapIconPaths.CHARACTER,
    alt: characterData.name,
    id: characterData.id,
  };

  return (
    <div className={additional_css} ref={containerRef}>
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
