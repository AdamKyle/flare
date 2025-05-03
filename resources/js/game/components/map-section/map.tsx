import { isNil } from 'lodash';
import React, { useLayoutEffect, useRef } from 'react';

import { MapApiUrls } from './api/enums/map-api-urls';
import useBaseMapDetailsApi from './api/hooks/use-base-map-details-api';
import DraggableMap from './draggable-map';
import { MapIconPaths } from './enums/map-icon-paths';
import { useOpenCharacterKingdomInfoModal } from './hooks/use-open-character-kingdom-info-modal';
import MapIcon from './types/map-icon';
import MapProps from './types/map-props';

import { GameDataError } from 'game-data/components/game-data-error';
import { useGameData } from 'game-data/hooks/use-game-data';

import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const Map = ({ additional_css, zoom }: MapProps) => {
  const containerRef = useRef<HTMLDivElement>(null);

  useLayoutEffect(() => {
    if (!containerRef.current) return;
    const rect = containerRef.current.getBoundingClientRect();
    console.log('🔍 [Step 1] container size:', rect.width, rect.height);
  }, []);

  const { gameData } = useGameData();

  const characterData = gameData?.character;

  if (!characterData) {
    return <GameDataError />;
  }

  const { openCharacterKingdomDetails } = useOpenCharacterKingdomInfoModal({
    character_id: characterData.id,
  });

  const { loading, error, data } = useBaseMapDetailsApi({
    url: MapApiUrls.BASE_MAP_DETAILS,
    urlParams: {
      character: characterData.id,
    },
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

  const characterPosition: MapIcon = {
    x: data.character_position.x_position,
    y: data.character_position.y_position,
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
