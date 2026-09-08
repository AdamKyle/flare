import clsx from 'clsx';
import React, { ReactNode } from 'react';
import ReactMarkdown from 'react-markdown';

import { GAME_MAP_EVENT_TYPE_LABELS } from '../enums/game-map-event-type';
import GameMapDetailProps from '../types/game-map-detail-props';
import GameMapFactualDefinition from '../types/game-map-factual-definition';

import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';

interface GameMapBonusEntry {
  label: string;
  percentage: number;
}

const resolveBonusEntries = (
  gameMap: GameMapFactualDefinition
): GameMapBonusEntry[] => {
  const entries: { label: string; value: number | null }[] = [
    { label: 'XP Bonus', value: gameMap.xp_bonus },
    { label: 'Skill XP Bonus', value: gameMap.skill_training_bonus },
    { label: 'Drop Chance Bonus', value: gameMap.drop_chance_bonus },
    { label: 'Enemy Stat Increase', value: gameMap.enemy_stat_bonus },
    {
      label: 'Character Damage Deduction',
      value: gameMap.character_attack_reduction,
    },
  ];

  return entries
    .map(({ label, value }) => ({
      label,
      percentage: (value ?? 0) * 100,
    }))
    .filter((entry) => entry.percentage !== 0);
};

const resolveRequiredQuestItemCopy = (
  gameMap: GameMapFactualDefinition
): string => {
  if (!gameMap.required_quest_item?.quest) {
    return 'No acquisition quest is currently assigned to this item.';
  }

  return `Obtain this item by completing ${gameMap.required_quest_item.quest.name}.`;
};

const GameMapDetail = ({
  game_map: gameMap,
  split_layout: splitLayout = false,
}: GameMapDetailProps): ReactNode => {
  const bonusEntries = resolveBonusEntries(gameMap);
  const hasAccessRows =
    gameMap.default ||
    gameMap.can_traverse ||
    gameMap.event_restriction !== null ||
    gameMap.required_location !== null ||
    Boolean(gameMap.kingdom_color);

  const renderDescription = (): ReactNode => {
    if (!gameMap.description) {
      return null;
    }

    return (
      <section>
        <h2 className="text-glacier-900 dark:text-glacier-100 mb-1 text-sm font-semibold">
          Description
        </h2>
        <div className="text-glacier-700 dark:text-glacier-300 min-w-0 text-sm break-words">
          <ReactMarkdown>{gameMap.description}</ReactMarkdown>
        </div>
      </section>
    );
  };

  const renderAccessConfiguration = (): ReactNode => {
    if (!hasAccessRows) {
      return null;
    }

    return (
      <section>
        <h2 className="text-glacier-900 dark:text-glacier-100 mb-2 text-sm font-semibold">
          Access and Configuration
        </h2>
        <Dl>
          {gameMap.default && (
            <>
              <Dt>Default map</Dt>
              <Dd>Yes</Dd>
            </>
          )}
          {gameMap.can_traverse && (
            <>
              <Dt>Can traverse</Dt>
              <Dd>Yes</Dd>
            </>
          )}
          {gameMap.event_restriction !== null && (
            <>
              <Dt>Event restriction</Dt>
              <Dd>{GAME_MAP_EVENT_TYPE_LABELS[gameMap.event_restriction]}</Dd>
            </>
          )}
          {gameMap.required_location && (
            <>
              <Dt>Required Location</Dt>
              <Dd>{gameMap.required_location.name}</Dd>
            </>
          )}
          {gameMap.kingdom_color && (
            <>
              <Dt>Kingdom color</Dt>
              <Dd>
                <span
                  role="img"
                  aria-label={`Kingdom color ${gameMap.kingdom_color}`}
                  style={{ backgroundColor: gameMap.kingdom_color }}
                  className="border-glacier-300 dark:border-glacier-700 inline-block h-6 w-12 rounded-md border"
                />
              </Dd>
            </>
          )}
        </Dl>
      </section>
    );
  };

  const renderRequiredQuestItem = (): ReactNode => {
    if (!gameMap.required_quest_item) {
      return null;
    }

    return (
      <section>
        <h2 className="text-glacier-900 dark:text-glacier-100 mb-1 text-sm font-semibold">
          Required Quest Item
        </h2>
        <p className="text-glacier-900 dark:text-glacier-100 font-medium">
          {gameMap.required_quest_item.name}
        </p>
        <p className="text-glacier-700 dark:text-glacier-300 text-sm">
          {resolveRequiredQuestItemCopy(gameMap)}
        </p>
      </section>
    );
  };

  const renderBonuses = (): ReactNode => {
    if (bonusEntries.length === 0) {
      return null;
    }

    return (
      <section>
        <h2 className="text-glacier-900 dark:text-glacier-100 mb-2 text-sm font-semibold">
          Map Bonuses
        </h2>
        <Dl>
          {bonusEntries.map((entry) => (
            <React.Fragment key={entry.label}>
              <Dt>{entry.label}</Dt>
              <Dd>{entry.percentage}%</Dd>
            </React.Fragment>
          ))}
        </Dl>
      </section>
    );
  };

  return (
    <div
      className={clsx(
        'gap-6',
        splitLayout ? 'grid md:grid-cols-2' : 'flex flex-col'
      )}
    >
      <div className="border-glacier-200 bg-glacier-950 dark:border-glacier-800 aspect-square w-full overflow-hidden rounded-md border">
        <img
          src={gameMap.map_url}
          alt={`${gameMap.name} map`}
          className="h-full w-full object-contain"
        />
      </div>
      <div className="flex flex-col gap-6">
        {renderDescription()}
        {renderAccessConfiguration()}
        {renderRequiredQuestItem()}
        {renderBonuses()}
      </div>
    </div>
  );
};

export default GameMapDetail;
