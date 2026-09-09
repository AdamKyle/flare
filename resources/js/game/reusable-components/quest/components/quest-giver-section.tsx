import React, { ReactNode } from 'react';

import FactualLink from '../../quest-item/partials/factual-link';
import QuestDetailProps from '../types/quest-detail-props';

import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';

const QuestGiverSection = ({
  quest,
  navigation,
}: QuestDetailProps): ReactNode => {
  const npc = quest.npc;

  if (!npc) {
    return null;
  }

  return (
    <div>
      <h3 className="text-marigold-700 dark:text-marigold-500 mb-2 text-base font-semibold">
        Quest Giver
      </h3>
      <Dl>
        <Dt>NPC</Dt>
        <Dd>
          <FactualLink
            id={npc.id}
            label={npc.name}
            on_click={navigation?.on_open_npc}
          />
        </Dd>
        {npc.game_map && (
          <>
            <Dt>Game Map</Dt>
            <Dd>
              <FactualLink
                id={npc.game_map.id}
                label={npc.game_map.name}
                on_click={navigation?.on_open_map}
              />
            </Dd>
          </>
        )}
        {npc.must_be_at_same_location && (
          <>
            <Dt>Must Be At Same Location</Dt>
            <Dd>Yes</Dd>
          </>
        )}
      </Dl>
    </div>
  );
};

export default QuestGiverSection;
