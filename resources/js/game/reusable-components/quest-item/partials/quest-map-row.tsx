import React from 'react';

import FactualLink from './factual-link';
import DefinitionRow from '../../viewable-sections/definition-row';
import InfoLabel from '../../viewable-sections/info-label';
import QuestMapRowProps from '../types/partials/quest-map-row-props';

const QuestMapRow = ({
  game_map: gameMap,
  on_open_map: onOpenMap,
}: QuestMapRowProps) => {
  if (gameMap == null) {
    return null;
  }

  return (
    <DefinitionRow
      left={<InfoLabel label="While On Map" />}
      right={
        <FactualLink
          id={gameMap.id}
          label={gameMap.name}
          on_click={onOpenMap}
        />
      }
    />
  );
};

export default QuestMapRow;
