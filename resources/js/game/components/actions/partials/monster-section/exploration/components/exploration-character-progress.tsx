import React, { ReactNode } from 'react';

import CurrencyDisplay from '../../../../../../reusable-components/currency/currency-display';
import { CurrencyDisplayMode } from '../../../../../../reusable-components/currency/enums/currency-display-mode';
import { CurrencyType } from '../../../../../../reusable-components/currency/enums/currency-type';

import { useGameData } from 'game-data/hooks/use-game-data';

import { formatNumberWithCommas } from 'game-utils/format-number';

import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import { ProgressBarSize } from 'ui/progress/enums/progress-bar-size';
import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import ProgressBar from 'ui/progress/progress-bar';

const ExplorationCharacterProgress = (): ReactNode => {
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

  const renderBalance = (currency: CurrencyType, amount: number): ReactNode => (
    <CurrencyDisplay
      currency={currency}
      amount={amount}
      display_mode={CurrencyDisplayMode.BALANCE}
      show_label={false}
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
      <Dl>
        <Dt>Gold</Dt>
        <Dd>{renderBalance(CurrencyType.GOLD, character.gold)}</Dd>
        <Dt>Gold Dust</Dt>
        <Dd>{renderBalance(CurrencyType.GOLD_DUST, character.gold_dust)}</Dd>
        <Dt>Shards</Dt>
        <Dd>{renderBalance(CurrencyType.SHARDS, character.shards)}</Dd>
        <Dt>Copper Coins</Dt>
        <Dd>
          {renderBalance(CurrencyType.COPPER_COINS, character.copper_coins)}
        </Dd>
      </Dl>
    </div>
  );
};

export default ExplorationCharacterProgress;
