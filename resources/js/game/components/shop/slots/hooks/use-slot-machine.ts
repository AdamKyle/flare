import { useCallback, useEffect, useMemo, useState } from 'react';

import UseSlotMachineDefinition from './definitions/use-slot-machine-definition';
import UseSlotMachineParams from './definitions/use-slot-machine-params';
import SpinSlotsRewardDefinition from '../api/definitions/spin-slots-reward-definition';
import { useSlots } from '../api/hooks/use-slots';
import { useSpinSlots } from '../api/hooks/use-spin-slots';
import SlotCooldown from '../types/slot-cooldown';
import { buildSlotCooldown } from '../utils/build-slot-cooldown';
import { buildSlotCurrencyUpdate } from '../utils/build-slot-currency-update';
import { resolveSlotSymbolPresentation } from '../utils/resolve-slot-symbol-presentation';
import { useSlotTimeoutWebsocket } from '../websockets/hooks/use-slot-timeout-websocket';

import { useGameData } from 'game-data/hooks/use-game-data';

const SLOT_REEL_COUNT = 3;

export const useSlotMachine = ({
  character_id,
  user_id,
}: UseSlotMachineParams): UseSlotMachineDefinition => {
  const { updateCharacter } = useGameData();
  const { data, loading, error: loadError } = useSlots();

  const symbols = useMemo(
    () => (data?.icons ?? []).map(resolveSlotSymbolPresentation),
    [data]
  );

  const {
    spin,
    loading: isSubmittingSpin,
    error: spinError,
  } = useSpinSlots({ character_id, symbol_count: symbols.length });

  const [serverCanSpin, setServerCanSpin] = useState(false);
  const [cooldown, setCooldown] = useState<SlotCooldown | null>(null);
  const [spinRequested, setSpinRequested] = useState(false);
  const [targetRolls, setTargetRolls] = useState<number[] | null>(null);
  const [stoppedReels, setStoppedReels] = useState(0);
  const [spinMessage, setSpinMessage] = useState<string | null>(null);
  const [spinReward, setSpinReward] =
    useState<SpinSlotsRewardDefinition | null>(null);

  const allReelsStopped =
    targetRolls !== null && stoppedReels >= SLOT_REEL_COUNT;
  const isSpinning = spinRequested && !allReelsStopped;
  const resultMessage = allReelsStopped ? spinMessage : null;
  const resultReward = allReelsStopped ? spinReward : null;

  const canSpin =
    !loading &&
    symbols.length > 0 &&
    serverCanSpin &&
    cooldown === null &&
    !isSpinning &&
    !isSubmittingSpin;

  useEffect(() => {
    if (!data) {
      return;
    }

    setServerCanSpin(data.can_spin);
    setCooldown(buildSlotCooldown(data.timeout_for));
  }, [data]);

  const handleTimeoutUpdate = useCallback((timeoutFor: number) => {
    setServerCanSpin(timeoutFor <= 0);
    setCooldown(buildSlotCooldown(timeoutFor));
  }, []);

  useSlotTimeoutWebsocket({
    user_id,
    enabled: user_id > 0,
    on_timeout_update: handleTimeoutUpdate,
  });

  const handleSpin = useCallback(() => {
    setSpinRequested(true);
    setTargetRolls(null);
    setStoppedReels(0);
    setSpinMessage(null);
    setSpinReward(null);

    const runSpin = async () => {
      const result = await spin();

      if (!result) {
        setSpinRequested(false);

        return;
      }

      updateCharacter(buildSlotCurrencyUpdate(result));
      setSpinMessage(result.message);
      setSpinReward(result.reward);
      setTargetRolls(result.rolls);
    };

    void runSpin();
  }, [spin, updateCharacter]);

  const handleReelStopped = useCallback(() => {
    setStoppedReels((previousCount) => previousCount + 1);
  }, []);

  return {
    symbols,
    spin_cost: data?.spin_cost ?? null,
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
  };
};
