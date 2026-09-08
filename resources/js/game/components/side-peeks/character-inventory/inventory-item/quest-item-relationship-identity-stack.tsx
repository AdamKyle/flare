import React, { ReactNode } from 'react';

import QuestItemRelationshipIdentityStackProps from './types/quest-item-relationship-identity-stack-props';
import {
  GameMapIdentityDefinition,
  LocationIdentityDefinition,
  MonsterIdentityDefinition,
} from '../../../../reusable-components/quest-item/types/quest-item-factual-definition';

import { StackedCardContentMode } from 'ui/cards/enums/stacked-card-content-mode';
import StackedCard from 'ui/cards/stacked-card';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';

const collectLocations = (
  questItem: QuestItemRelationshipIdentityStackProps['quest_item']
): LocationIdentityDefinition[] =>
  [
    questItem.drop_location,
    ...(questItem.required_locations ?? []),
    ...(questItem.reward_locations ?? []),
  ].filter((location): location is LocationIdentityDefinition => !!location);

const collectMaps = (
  questItem: QuestItemRelationshipIdentityStackProps['quest_item']
): GameMapIdentityDefinition[] => [
  ...collectLocations(questItem).map((location) => location.game_map),
  ...(questItem.required_monsters ?? []).map((monster) => monster.game_map),
];

const QuestItemRelationshipIdentityStack = ({
  quest_item: questItem,
  kind,
  id,
  on_close: onClose,
  on_open_map: onOpenMap,
}: QuestItemRelationshipIdentityStackProps): ReactNode => {
  const renderLocation = (): ReactNode => {
    const location = collectLocations(questItem).find(
      (candidate) => candidate.id === id
    );

    if (!location) {
      return <p>This Location is no longer available.</p>;
    }

    return (
      <Dl>
        <Dt>Location</Dt>
        <Dd>{location.name}</Dd>
        <Dt>Map</Dt>
        <Dd>
          <button
            type="button"
            onClick={() => onOpenMap(location.game_map.id)}
            className="text-danube-700 hover:text-danube-600 focus-visible:ring-danube-400 dark:text-danube-200 dark:hover:text-danube-100 decoration-danube-400 dark:decoration-danube-500 rounded-sm font-medium underline underline-offset-2 focus:outline-none focus-visible:ring-2"
          >
            {location.game_map.name}
          </button>
        </Dd>
      </Dl>
    );
  };

  const renderMap = (): ReactNode => {
    const gameMap = collectMaps(questItem).find(
      (candidate) => candidate.id === id
    );

    if (!gameMap) {
      return <p>This Map is no longer available.</p>;
    }

    return (
      <Dl>
        <Dt>Map</Dt>
        <Dd>{gameMap.name}</Dd>
      </Dl>
    );
  };

  const renderMonster = (): ReactNode => {
    const monster = (questItem.required_monsters ?? []).find(
      (candidate): candidate is MonsterIdentityDefinition => candidate.id === id
    );

    if (!monster) {
      return <p>This Monster is no longer available.</p>;
    }

    return (
      <Dl>
        <Dt>Monster</Dt>
        <Dd>{monster.name}</Dd>
        <Dt>Map</Dt>
        <Dd>
          <button
            type="button"
            onClick={() => onOpenMap(monster.game_map.id)}
            className="text-danube-700 hover:text-danube-600 focus-visible:ring-danube-400 dark:text-danube-200 dark:hover:text-danube-100 decoration-danube-400 dark:decoration-danube-500 rounded-sm font-medium underline underline-offset-2 focus:outline-none focus-visible:ring-2"
          >
            {monster.game_map.name}
          </button>
        </Dd>
        {monster.quest_item_drop_chance != null && (
          <>
            <Dt>Drop Chance</Dt>
            <Dd>{monster.quest_item_drop_chance}%</Dd>
          </>
        )}
      </Dl>
    );
  };

  const renderContent = (): ReactNode => {
    if (kind === 'location') {
      return renderLocation();
    }

    if (kind === 'monster') {
      return renderMonster();
    }

    return renderMap();
  };

  return (
    <StackedCard
      on_close={onClose}
      aria_label="Relationship Details"
      content_mode={StackedCardContentMode.FULL_BLEED}
    >
      <div className="min-h-0 flex-1 overflow-y-auto px-4 py-4 sm:px-5">
        {renderContent()}
      </div>
    </StackedCard>
  );
};

export default QuestItemRelationshipIdentityStack;
