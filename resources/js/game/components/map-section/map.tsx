import { isNil } from 'lodash';
import React from 'react';

import { MapApiUrls } from './api/enums/map-api-urls';
import useBaseMapDetailsApi from './api/hooks/use-base-map-details-api';
import DraggableMap from './draggable-map';
import MapProps from './types/map-props';

import { GameDataError } from 'game-data/components/game-data-error';
import { useGameData } from 'game-data/hooks/use-game-data';

import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const Map = ({ additional_css }: MapProps) => {
  const { gameData } = useGameData();

  const characterData = gameData?.character;

  if (!characterData) {
    return <GameDataError />;
  }

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

  const tiles = data.tiles;

  return (
    <div className={additional_css}>
      <DraggableMap additional_css={'w-full h-full'} tiles={tiles} />
    </div>
  );
};

export default Map;
