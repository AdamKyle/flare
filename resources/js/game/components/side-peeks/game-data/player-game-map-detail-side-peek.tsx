import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode } from 'react';

import PlayerGameMapDetailSidePeekProps from './types/player-game-map-detail-side-peek-props';
import { usePlayerGameMapDetail } from '../../../reusable-components/game-map/api/hooks/use-player-game-map-detail';
import GameMapDetail from '../../../reusable-components/game-map/components/game-map-detail';

import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const PlayerGameMapDetailSidePeek = ({
  game_map_id: gameMapId,
}: PlayerGameMapDetailSidePeekProps): ReactNode => {
  const {
    game_map: gameMap,
    loading,
    error,
  } = usePlayerGameMapDetail(gameMapId);

  if (loading) {
    return (
      <div className="px-4">
        <InfiniteLoader />
      </div>
    );
  }

  if (error || !gameMap) {
    return (
      <div className="px-4">
        <ApiErrorAlert
          apiError={error?.message ?? 'Unable to load this Game Map.'}
        />
      </div>
    );
  }

  return (
    <div className="flex flex-col gap-4 px-4">
      <h1 className="text-glacier-900 dark:text-glacier-100 text-xl font-semibold">
        {gameMap.name}
      </h1>
      <GameMapDetail game_map={gameMap} />
    </div>
  );
};

export default PlayerGameMapDetailSidePeek;
