import React from 'react';

import FactualLink from './factual-link';
import QuestMapRow from './quest-map-row';
import DefinitionRow from '../../viewable-sections/definition-row';
import InfoLabel from '../../viewable-sections/info-label';
import QuestRowsProps from '../types/partials/quest-rows-props';

const QuestRows = ({
  heading,
  quest,
  on_open_quest: onOpenQuest,
  on_open_npc: onOpenNpc,
  on_open_map: onOpenMap,
}: QuestRowsProps) => {
  return (
    <>
      <DefinitionRow
        left={<InfoLabel label={heading} />}
        right={
          <FactualLink
            id={quest.id}
            label={quest.name}
            on_click={onOpenQuest}
          />
        }
      />
      {quest.npc ? (
        <DefinitionRow
          left={<InfoLabel label="For NPC" />}
          right={
            <FactualLink
              id={quest.npc.id}
              label={quest.npc.name}
              on_click={onOpenNpc}
            />
          }
        />
      ) : null}
      <QuestMapRow game_map={quest.game_map} on_open_map={onOpenMap} />
    </>
  );
};

export default QuestRows;
