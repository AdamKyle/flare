import React, { ReactNode } from 'react';

import FactualLink from './factual-link';
import { relationshipRowClassName } from './relationship-row-styles';
import QuestRowsProps from '../types/partials/quest-rows-props';

/**
 * Compact clickable Quest relationship row: the Quest name is the primary
 * row title, with the relationship label, Quest Giver NPC, and Game Map
 * shown as independently clickable identities on a secondary line.
 */
const QuestRows = ({
  heading,
  quest,
  on_open_quest: onOpenQuest,
  on_open_npc: onOpenNpc,
  on_open_map: onOpenMap,
}: QuestRowsProps): ReactNode => {
  const renderNpc = (): ReactNode => {
    if (!quest.npc) {
      return null;
    }

    return (
      <>
        {' · '}
        <FactualLink
          id={quest.npc.id}
          label={quest.npc.name}
          on_click={onOpenNpc}
        />
      </>
    );
  };

  const renderMap = (): ReactNode => {
    if (!quest.game_map) {
      return null;
    }

    return (
      <>
        {' · '}
        <FactualLink
          id={quest.game_map.id}
          label={quest.game_map.name}
          on_click={onOpenMap}
        />
      </>
    );
  };

  return (
    <div className={relationshipRowClassName}>
      <p className="text-glacier-900 dark:text-glacier-100 font-medium">
        <FactualLink id={quest.id} label={quest.name} on_click={onOpenQuest} />
      </p>
      <p className="text-glacier-600 dark:text-glacier-400 text-xs">
        {heading}
        {renderNpc()}
        {renderMap()}
      </p>
    </div>
  );
};

export default QuestRows;
