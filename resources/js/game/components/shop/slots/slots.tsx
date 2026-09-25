import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode } from 'react';

import SlotReel from './components/slot-reel';
import SlotSpinReward from './components/slot-spin-reward';
import { useSlotMachine } from './hooks/use-slot-machine';
import CurrencyDisplay from '../../../reusable-components/currency/currency-display';
import { CurrencyDisplayMode } from '../../../reusable-components/currency/enums/currency-display-mode';
import { CurrencyType } from '../../../reusable-components/currency/enums/currency-type';

import { useGameData } from 'game-data/hooks/use-game-data';

import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import LoadingButton from 'ui/buttons/loading-button';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';
import TimerBarSize from 'ui/timer-bar/enums/timer-bar-size';
import TimerBar from 'ui/timer-bar/timer-bar';

const SLOT_REEL_INDEXES = [0, 1, 2];

const Slots = (): ReactNode => {
  const { gameData } = useGameData();

  const {
    symbols,
    spin_cost: spinCost,
    loading,
    load_error: loadError,
    spin_error: spinError,
    is_spinning: isSpinning,
    can_spin: canSpin,
    cooldown,
    target_rolls: targetRolls,
    result_message: resultMessage,
    result_reward: resultReward,
    handle_spin: handleSpin,
    handle_reel_stopped: handleReelStopped,
  } = useSlotMachine({
    character_id: gameData?.character?.id ?? 0,
    user_id: gameData?.character?.user_id ?? 0,
  });

  const currentGold = gameData?.character?.gold ?? null;

  const renderReel = (reelIndex: number): ReactNode => (
    <SlotReel
      key={`slot-reel-${reelIndex}`}
      reel_index={reelIndex}
      symbols={symbols}
      is_spinning={isSpinning}
      target_index={targetRolls?.[reelIndex] ?? null}
      on_stopped={handleReelStopped}
    />
  );

  const renderCooldown = (): ReactNode => {
    if (!cooldown) {
      return null;
    }

    return (
      <TimerBar
        length={cooldown.length}
        complete_at={cooldown.ends_at}
        title="Slot machine cooling down"
        size={TimerBarSize.THIN}
      />
    );
  };

  const renderSpinError = (): ReactNode => {
    if (!spinError) {
      return null;
    }

    return <ApiErrorAlert apiError={spinError.message} />;
  };

  const renderResultMessage = (): ReactNode => {
    if (!resultMessage) {
      return null;
    }

    return (
      <p className="text-center font-semibold text-gray-900 dark:text-gray-100">
        {resultMessage}
      </p>
    );
  };

  const renderResultReward = (): ReactNode => {
    if (!resultReward) {
      return null;
    }

    return <SlotSpinReward reward={resultReward} />;
  };

  const renderSpinCost = (): ReactNode => {
    if (spinCost === null) {
      return null;
    }

    return (
      <p className="flex flex-wrap items-center gap-1 text-sm">
        <span>Cost per spin:</span>
        <CurrencyDisplay
          currency={CurrencyType.GOLD}
          amount={spinCost}
          display_mode={CurrencyDisplayMode.EXACT}
          additional_css="font-semibold"
        />
      </p>
    );
  };

  const renderCurrentGold = (): ReactNode => {
    if (currentGold === null) {
      return null;
    }

    return (
      <p className="flex flex-wrap items-center gap-1 text-sm">
        <span>You Have:</span>
        <CurrencyDisplay
          currency={CurrencyType.GOLD}
          amount={currentGold}
          display_mode={CurrencyDisplayMode.BALANCE}
          additional_css="font-semibold"
        />
      </p>
    );
  };

  const renderMachine = (): ReactNode => {
    if (loading) {
      return <InfiniteLoader />;
    }

    if (loadError) {
      return <ApiErrorAlert apiError={loadError.message} />;
    }

    return (
      <div className="space-y-4">
        <div className="flex gap-2">{SLOT_REEL_INDEXES.map(renderReel)}</div>
        <div role="status" aria-live="polite" className="min-h-6 space-y-2">
          {renderResultMessage()}
          {renderResultReward()}
        </div>
        {renderSpinError()}
        {renderCooldown()}
        <LoadingButton
          label="Spin"
          loading_label="Spinning..."
          variant={ButtonVariant.PRIMARY}
          on_click={handleSpin}
          is_loading={isSpinning}
          disabled={!canSpin}
          additional_css="w-full"
          aria_label="Spin the slot machine"
        />
      </div>
    );
  };

  return (
    <div className="space-y-4 text-gray-800 dark:text-gray-200">
      <p className="text-sm">
        Match two or three currency symbols to win Gold Dust, Shards or Copper
        Coins.
      </p>
      <div className="space-y-1">
        {renderSpinCost()}
        {renderCurrentGold()}
      </div>
      <a
        href="/information/slots"
        target="_blank"
        rel="noopener noreferrer"
        className="text-danube-700 focus:ring-danube-500 dark:text-danube-300 inline-block text-sm font-semibold underline focus:ring-2 focus:outline-none"
      >
        Slots help
        <span className="sr-only"> (opens in a new tab)</span>
      </a>
      {renderMachine()}
    </div>
  );
};

export default Slots;
