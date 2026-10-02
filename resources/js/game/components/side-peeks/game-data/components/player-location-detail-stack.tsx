import React, { ReactNode, useState } from 'react';

import PlayerGameMapDetailStack from './player-game-map-detail-stack';
import PlayerLocationDetailBody from './player-location-detail-body';
import PlayerLocationDetailStackProps from './types/player-location-detail-stack-props';

import { StackedCardContentMode } from 'ui/cards/enums/stacked-card-content-mode';
import StackedCard from 'ui/cards/stacked-card';

const PlayerLocationDetailStack = ({
  location_id: locationId,
  on_close: onClose,
}: PlayerLocationDetailStackProps): ReactNode => {
  const [nestedGameMapId, setNestedGameMapId] = useState<number | null>(null);

  const renderNestedGameMap = (): ReactNode => {
    if (nestedGameMapId === null) {
      return null;
    }

    return (
      <PlayerGameMapDetailStack
        game_map_id={nestedGameMapId}
        on_close={() => setNestedGameMapId(null)}
      />
    );
  };

  return (
    <StackedCard
      on_close={onClose}
      aria_label="Location Details"
      content_mode={StackedCardContentMode.FULL_BLEED}
    >
      <div className="relative flex h-full min-h-0 flex-col overflow-hidden">
        <div className="min-h-0 flex-1 overflow-y-auto py-4">
          <PlayerLocationDetailBody
            location_id={locationId}
            on_open_map={setNestedGameMapId}
          />
        </div>

        {renderNestedGameMap()}
      </div>
    </StackedCard>
  );
};

export default PlayerLocationDetailStack;
