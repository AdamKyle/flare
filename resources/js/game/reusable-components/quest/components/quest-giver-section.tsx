import React, { ReactNode } from 'react';

import FactualLink from '../../quest-item/partials/factual-link';
import QuestDetailProps from '../types/quest-detail-props';

import Card from 'ui/cards/card';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';

const QuestGiverSection = ({
  quest,
  navigation,
}: QuestDetailProps): ReactNode => {
  const npc = quest.npc;

  const renderGameMap = (): ReactNode => {
    if (!npc?.game_map) {
      return 'None';
    }

    return (
      <FactualLink
        id={npc.game_map.id}
        label={npc.game_map.name}
        on_click={navigation?.on_open_map}
      />
    );
  };

  const renderQuestGiver = (): ReactNode => {
    if (!npc) {
      return (
        <p className="text-glacier-500 dark:text-glacier-400 text-sm">
          No Quest Giver NPC set.
        </p>
      );
    }

    return (
      <Dl>
        <Dt>NPC</Dt>
        <Dd>
          <FactualLink
            id={npc.id}
            label={npc.name}
            on_click={navigation?.on_open_npc}
          />
        </Dd>
        <Dt>Game Map</Dt>
        <Dd>{renderGameMap()}</Dd>
        <Dt>Must Be At Same Location</Dt>
        <Dd>{npc.must_be_at_same_location ? 'Yes' : 'No'}</Dd>
      </Dl>
    );
  };

  return (
    <Card>
      <h2 className="text-glacier-900 dark:text-glacier-100 mb-2 text-sm font-semibold">
        Quest Giver
      </h2>
      {renderQuestGiver()}
    </Card>
  );
};

export default QuestGiverSection;
