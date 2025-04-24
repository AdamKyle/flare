import { isNil } from 'lodash';
import React from 'react';

import { MapApiUrls } from './api/enums/map-api-urls';
import useBaseMapDetailsApi from './api/hooks/use-base-map-details-api';
import Map from './map';
import FullMapProps from './types/full-map-props';

import { GameDataError } from 'game-data/components/game-data-error';
import { useGameData } from 'game-data/hooks/use-game-data';

import Card from 'ui/cards/card';
import ContainerWithTitle from 'ui/container/container-with-title';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const FullMap = ({ close_map }: FullMapProps) => {
  const { gameData } = useGameData();

  const characterData = gameData?.character;

  if (!characterData) {
    return (
      <ContainerWithTitle
        manageSectionVisibility={close_map}
        title={'An error occurred'}
      >
        <Card>
          <GameDataError />
        </Card>
      </ContainerWithTitle>
    );
  }

  const { loading, error, data } = useBaseMapDetailsApi({
    url: MapApiUrls.BASE_MAP_DETAILS,
    urlParams: {
      character: characterData.id,
    },
  });

  if (loading) {
    return (
      <ContainerWithTitle
        manageSectionVisibility={close_map}
        title={'Fetching Map Data ...'}
      >
        <Card>
          <InfiniteLoader />
        </Card>
      </ContainerWithTitle>
    );
  }

  if (isNil(data) || error) {
    return (
      <ContainerWithTitle
        manageSectionVisibility={close_map}
        title={'An error occurred'}
      >
        <Card>
          <GameDataError />
        </Card>
      </ContainerWithTitle>
    );
  }

  return (
    <ContainerWithTitle
      manageSectionVisibility={close_map}
      title={'Map: Surface'}
    >
      <Card>
        <Map additional_css={'h-[550px]'} />
      </Card>
    </ContainerWithTitle>
  );
};

export default FullMap;
