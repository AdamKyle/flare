import ApiErrorAlert from 'api-handler/components/api-error-alert';
import { isNil } from 'lodash';
import React, { ReactNode, useEffect } from 'react';

import { useFetchMonsterStatsApi } from './api/hooks/use-fetch-monster-stats-api';
import MonsterStatSectionProps from './types/monster-stat-section-props';
import MonsterDetail from '../../../../reusable-components/monster/components/monster-detail';
import { SidePeekComponentRegistrationEnum } from '../../../side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek } from '../../../side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../side-peeks/base/hooks/use-side-peek-emitter';

import { useGameData } from 'game-data/hooks/use-game-data';

import ContainerWithTitle from 'ui/container/container-with-title';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

export const MonsterStatSection = ({
  monster_id,
  toggle_monster_stat_visibility,
}: MonsterStatSectionProps): ReactNode => {
  const { gameData } = useGameData();
  const { loading, data, error, setRequestParams } = useFetchMonsterStatsApi();
  const sidePeekEmitter = useSidePeekEmitter();

  useEffect(() => {
    if (monster_id === 0 || !gameData?.character) {
      return;
    }

    setRequestParams({
      character_id: gameData.character.id,
      monster_id,
    });
  }, [gameData?.character, monster_id, setRequestParams]);

  const handleOpenMap = (gameMapId: number): void => {
    sidePeekEmitter.emit(
      SidePeek.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.PLAYER_GAME_MAP_DETAIL,
      {
        is_open: true,
        title: 'Game Map Details',
        allow_clicking_outside: true,
        game_map_id: gameMapId,
      }
    );
  };

  const handleOpenItem = (itemId: number): void => {
    sidePeekEmitter.emit(
      SidePeek.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.ITEM_DETAILS,
      {
        is_open: true,
        title: 'Item Details',
        allow_clicking_outside: true,
        item_id: itemId,
      }
    );
  };

  if (!isNil(error)) {
    return (
      <ContainerWithTitle
        manageSectionVisibility={() => toggle_monster_stat_visibility(0)}
        title={'something went wrong'}
      >
        <ApiErrorAlert apiError={error.message} />
      </ContainerWithTitle>
    );
  }

  if (isNil(data) || loading) {
    return (
      <ContainerWithTitle
        manageSectionVisibility={() => toggle_monster_stat_visibility(0)}
        title={'Fetching Monster'}
      >
        <InfiniteLoader />
      </ContainerWithTitle>
    );
  }

  return (
    <ContainerWithTitle
      manageSectionVisibility={() => toggle_monster_stat_visibility(0)}
      title={data.identity.name}
    >
      <MonsterDetail
        monster={data}
        presentation="page"
        navigation={{
          on_open_map: handleOpenMap,
          on_open_item: handleOpenItem,
        }}
        initial_context_tab={data.gem_effect_contexts.length > 0}
      />
    </ContainerWithTitle>
  );
};
