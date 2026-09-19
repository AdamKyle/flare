import clsx from 'clsx';
import React, { ReactNode } from 'react';

import { useGameData } from 'game-data/hooks/use-game-data';

import {
  formatNumberWithCommas,
  shortenNumber,
} from 'game-utils/format-number';

import { ProgressBarSize } from 'ui/progress/enums/progress-bar-size';
import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import ProgressBar from 'ui/progress/progress-bar';
import Separator from 'ui/separator/separator';
import GeneralToolTip from 'ui/tool-tips/general-tool-tip';

interface CurrencyDisplay {
  key: string;
  label: string;
  value: number;
  icon_class: string;
  icon_color_class: string;
  description?: string;
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

  const currencies: CurrencyDisplay[] = [
    {
      key: 'gold',
      label: 'Gold',
      value: character.gold,
      icon_class: 'ra ra-gold-bar',
      icon_color_class: 'text-marigold-600 dark:text-marigold-400',
      description: 'Gold is the key currency of the game.',
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

  const renderCurrency = (currency: CurrencyDisplay): ReactNode => {
    const fullAmount = `${currency.label}: ${formatNumberWithCommas(currency.value)}`;

    const tooltipContent = (
      <div className="space-y-2">
        <p className={clsx('font-semibold', currency.icon_color_class)}>
          {currency.label}
        </p>
        {currency.description && <p>{currency.description}</p>}
        <Separator />
        <div className="flex items-center gap-2">
          <i
            className={`${currency.icon_class} ${currency.icon_color_class} text-base`}
            aria-hidden="true"
          />
          <span>{fullAmount}</span>
        </div>
      </div>
    );

    return (
      <GeneralToolTip
        key={currency.key}
        label={currency.label}
        message={tooltipContent}
        placement="above"
        size="md"
        trigger_aria_label={fullAmount}
        trigger={
          <span className="flex items-center gap-1.5">
            <i
              className={`${currency.icon_class} ${currency.icon_color_class} text-base`}
              aria-hidden="true"
            />
            <span aria-hidden="true">{shortenNumber(currency.value)}</span>
          </span>
        }
      />
    );
  };

  return (
    <div className="space-y-2">
      <ProgressBar
        label={`Lv. ${character.level}`}
        value={character.xp}
        max={Math.max(character.xp_next, 1)}
        size={ProgressBarSize.THIN}
        variant={ProgressBarVariant.XP}
        value_label={
          isMaxLevel
            ? 'Max'
            : `${formatNumberWithCommas(character.xp)}/${formatNumberWithCommas(character.xp_next)}`
        }
        aria_label={`Character level ${character.level} experience progress`}
      />
      <div className="grid grid-cols-2 gap-x-4 gap-y-1 text-sm text-gray-800 lg:grid-cols-4 dark:text-gray-200">
        {currencies.map(renderCurrency)}
      </div>
    </div>
  );
};

export default CharacterCombatStatus;
