import React, { ReactNode } from 'react';

import PlayerGameMapDetailBody from './player-game-map-detail-body';
import PlayerGameMapDetailStackProps from './types/player-game-map-detail-stack-props';

import { StackedCardContentMode } from 'ui/cards/enums/stacked-card-content-mode';
import StackedCard from 'ui/cards/stacked-card';

const PlayerGameMapDetailStack = ({
  game_map_id: gameMapId,
  on_close: onClose,
}: PlayerGameMapDetailStackProps): ReactNode => {
  return (
    <StackedCard
      on_close={onClose}
      aria_label="Game Map Details"
      content_mode={StackedCardContentMode.FULL_BLEED}
    >
      <div className="min-h-0 flex-1 overflow-y-auto py-4">
        <PlayerGameMapDetailBody game_map_id={gameMapId} />
      </div>
    </StackedCard>
  );
};

export default PlayerGameMapDetailStack;
