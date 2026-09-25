import React, { ReactNode } from 'react';

import CombatStatusCurrency from './types/combat-status-currency';
import CurrencyDisplay from '../../../../reusable-components/currency/currency-display';
import { CurrencyDisplayMode } from '../../../../reusable-components/currency/enums/currency-display-mode';
import { CurrencyType } from '../../../../reusable-components/currency/enums/currency-type';

import { useGameData } from 'game-data/hooks/use-game-data';

import { formatNumberWithCommas } from 'game-utils/format-number';

import { ProgressBarSize } from 'ui/progress/enums/progress-bar-size';
import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import ProgressBar from 'ui/progress/progress-bar';

const CharacterCombatStatus = (): ReactNode => {
  const { gameData } = useGameData();
  const character = gameData?.character ?? null;

  if (!character) {
    return null;
  }

  const progression = gameData?.battleRewardProgression ?? null;
  const displayedLevel = progression?.level ?? character.level;
  const displayedXp = progression?.xp ?? character.xp;
  const displayedXpNext = progression?.xp_next ?? character.xp_next;

  const isMaxLevel = displayedLevel >= character.max_level;

  const currencies: CombatStatusCurrency[] = [
    { currency: CurrencyType.GOLD, amount: character.gold },
    { currency: CurrencyType.GOLD_DUST, amount: character.gold_dust },
    { currency: CurrencyType.SHARDS, amount: character.shards },
    { currency: CurrencyType.COPPER_COINS, amount: character.copper_coins },
  ];

  const renderCurrency = (currency: CombatStatusCurrency): ReactNode => (
    <CurrencyDisplay
      key={currency.currency}
      currency={currency.currency}
      amount={currency.amount}
      display_mode={CurrencyDisplayMode.BALANCE}
      show_label={false}
      additional_css="gap-1.5 text-base"
    />
  );

  return (
    <div className="space-y-2">
      <ProgressBar
        label={`Lv. ${displayedLevel}`}
        value={displayedXp}
        max={Math.max(displayedXpNext, 1)}
        size={ProgressBarSize.THIN}
        variant={ProgressBarVariant.XP}
        value_label={
          isMaxLevel
            ? 'Max'
            : `${formatNumberWithCommas(displayedXp)}/${formatNumberWithCommas(displayedXpNext)}`
        }
        aria_label={`Character level ${displayedLevel} experience progress`}
      />
      <div className="grid grid-cols-2 gap-x-4 gap-y-1 text-sm text-gray-800 lg:grid-cols-4 dark:text-gray-200">
        {currencies.map(renderCurrency)}
      </div>
    </div>
  );
};

export default CharacterCombatStatus;
