import ApiErrorAlert from 'api-handler/components/api-error-alert';
import { isNil } from 'lodash';
import React, { ReactNode, useEffect } from 'react';

import { useFetchMonsterStatsApi } from './api/hooks/use-fetch-monster-stats-api';
import MonsterStatDetails from './monster-stat-details';
import MonsterStatSectionProps from './types/monster-stat-section-props';

import { useGameData } from 'game-data/hooks/use-game-data';

import Card from 'ui/cards/card';
import ContainerWithTitle from 'ui/container/container-with-title';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

export const MonsterStatSection = ({
  monster_id,
  toggle_monster_stat_visibility,
}: MonsterStatSectionProps): ReactNode => {
  const { gameData } = useGameData();
  const { loading, data, error, setRequestParams } = useFetchMonsterStatsApi();

  useEffect(() => {
    if (monster_id === 0 || !gameData?.character) {
      return;
    }

    setRequestParams({
      character_id: gameData.character.id,
      monster_id,
    });
  }, [gameData?.character, monster_id, setRequestParams]);

  if (!isNil(error)) {
    return (
      <ContainerWithTitle
        manageSectionVisibility={() => toggle_monster_stat_visibility(0)}
        title={'something went wrong'}
      >
        <Card>
          <ApiErrorAlert apiError={error.message} />
        </Card>
      </ContainerWithTitle>
    );
  }

  if (isNil(data) || loading) {
    return (
      <ContainerWithTitle
        manageSectionVisibility={() => toggle_monster_stat_visibility(0)}
        title={'Fetching Monster'}
      >
        <Card>
          <InfiniteLoader />
        </Card>
      </ContainerWithTitle>
    );
  }

  return (
    <ContainerWithTitle
      manageSectionVisibility={() => toggle_monster_stat_visibility(0)}
      title={data.name}
    >
      <Card>
        <MonsterStatDetails monster={data} />
      </Card>
    </ContainerWithTitle>
  );
};
