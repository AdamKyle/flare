import React, { ReactNode } from 'react';

import PlayerNpcDetailBody from './player-npc-detail-body';
import PlayerNpcDetailStackProps from './types/player-npc-detail-stack-props';

import { StackedCardContentMode } from 'ui/cards/enums/stacked-card-content-mode';
import StackedCard from 'ui/cards/stacked-card';

const PlayerNpcDetailStack = ({
  npc_id: npcId,
  on_close: onClose,
}: PlayerNpcDetailStackProps): ReactNode => {
  return (
    <StackedCard
      on_close={onClose}
      aria_label="NPC Details"
      content_mode={StackedCardContentMode.FULL_BLEED}
    >
      <div className="min-h-0 flex-1 overflow-y-auto py-4">
        <PlayerNpcDetailBody npc_id={npcId} />
      </div>
    </StackedCard>
  );
};

export default PlayerNpcDetailStack;
