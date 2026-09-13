import React, { ReactNode } from 'react';

import { useGameData } from 'game-data/hooks/use-game-data';

import { shortenNumber } from 'game-utils/format-number';

import { ProgressBarSize } from 'ui/progress/enums/progress-bar-size';
import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import ProgressBar from 'ui/progress/progress-bar';

interface CurrencyDisplay {
  key: string;
  label: string;
  value: number;
  icon_class: string;
  icon_color_class: string;
}

/**
 * Compact, always-live Character level/XP and currency strip for manual
 * combat. Reads directly from the globally mounted Character state so it
 * rerenders on the same websocket-driven updates that already keep the rest
 * of the game live, with no fetching or duplicated state of its own.
 */
const CharacterCombatStatus = (): ReactNode => {
  const { gameData } = useGameData();
  const character = gameData?.character ?? null;

  if (!character) {
    return null;
  }

  const isMaxLevel = character.level >= character.max_level;
  const xpPercent = Math.min(
    Math.round((character.xp / Math.max(character.xp_next, 1)) * 100),
    100
  );

  const currencies: CurrencyDisplay[] = [
    {
      key: 'gold',
      label: 'Gold',
      value: character.gold,
      icon_class: 'ra ra-gold-bar',
      icon_color_class: 'text-marigold-600 dark:text-marigold-400',
    },
    {
      key: 'gold_dust',
      label: 'Gold Dust',
      value: character.gold_dust,
      icon_class: 'fas fa-magic',
      icon_color_class: 'text-indigo-500 dark:text-indigo-300',
    },
    {
      key: 'shards',
      label: 'Shards',
      value: character.shards,
      icon_class: 'ra ra-crystals',
      icon_color_class: 'text-glacier-600 dark:text-glacier-400',
    },
    {
      key: 'copper_coins',
      label: 'Copper Coins',
      value: character.copper_coins,
      icon_class: 'fas fa-coins',
      icon_color_class: 'text-mango-tango-600 dark:text-mango-tango-400',
    },
  ];

  return (
    <div className="space-y-2">
      <ProgressBar
        label={`Lv. ${character.level}`}
        value={character.xp}
        max={Math.max(character.xp_next, 1)}
        size={ProgressBarSize.THIN}
        variant={ProgressBarVariant.SUMMER}
        value_label={isMaxLevel ? 'Max' : `${xpPercent}%`}
        aria_label={`Character level ${character.level} experience progress`}
      />
      <div className="grid grid-cols-2 gap-x-4 gap-y-1 text-sm text-gray-800 lg:grid-cols-4 dark:text-gray-200">
        {currencies.map((currency) => (
          <div
            key={currency.key}
            className="flex items-center gap-1.5"
            title={currency.label}
          >
            <i
              className={`${currency.icon_class} ${currency.icon_color_class} text-base`}
              aria-hidden="true"
            />
            <span className="sr-only">{currency.label}:</span>
            <span>{shortenNumber(currency.value)}</span>
          </div>
        ))}
      </div>
    </div>
  );
};

export default CharacterCombatStatus;
