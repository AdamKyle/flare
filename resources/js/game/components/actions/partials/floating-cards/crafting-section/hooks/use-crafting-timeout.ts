import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { useEffect, useRef, useState } from 'react';

import { CraftingApiUrls } from '../api/enums/crafting-api-urls';
import { formatCraftingTimeout } from '../utils/format-crafting-timeout';
import CraftingTimeoutState from './definitions/crafting-timeout-state';
import UseCraftingTimeoutDefinition from './definitions/use-crafting-timeout-definition';
import { useCraftingTimeoutWebsocket } from '../websockets/hooks/use-crafting-timeout-websocket';

import CharacterSheetDefinition from 'game-data/api-data-definitions/character/character-sheet-definition';

let trackedCharacterId: number | null = null;
let craftingTimeoutEndsAt: number | null = null;
let craftingTimeoutDuration = 0;
let craftingCanCraft = true;
let hasInitializedCharacterTimeout = false;
let craftingTimeoutResolved = false;

const getRemainingSeconds = (): number => {
  if (craftingTimeoutEndsAt === null) {
    return 0;
  }

  return Math.max(Math.ceil((craftingTimeoutEndsAt - Date.now()) / 1000), 0);
};

const initializeTimeoutState = (
  characterData: CharacterSheetDefinition | null | undefined
): CraftingTimeoutState => {
  const characterId = characterData?.id ?? 0;

  if (trackedCharacterId !== characterId) {
    trackedCharacterId = characterId;
    craftingTimeoutEndsAt = null;
    craftingTimeoutDuration = 0;
    craftingCanCraft = characterData?.can_craft !== false;
    hasInitializedCharacterTimeout = false;
    craftingTimeoutResolved = false;
  }

  const storedRemainingSeconds = getRemainingSeconds();

  if (storedRemainingSeconds > 0) {
    return {
      remainingSeconds: storedRemainingSeconds,
      totalSeconds: craftingTimeoutDuration,
      canCraft: false,
    };
  }

  if (hasInitializedCharacterTimeout) {
    return {
      remainingSeconds: 0,
      totalSeconds: 0,
      canCraft: craftingTimeoutResolved ? true : craftingCanCraft,
    };
  }

  hasInitializedCharacterTimeout = true;

  const initialTimeout = Math.max(characterData?.can_craft_again_at ?? 0, 0);

  if (initialTimeout > 0) {
    craftingTimeoutEndsAt = Date.now() + initialTimeout * 1000;
    craftingTimeoutDuration = initialTimeout;
    craftingCanCraft = false;
    craftingTimeoutResolved = false;
  }

  return {
    remainingSeconds: initialTimeout,
    totalSeconds: initialTimeout,
    canCraft: initialTimeout > 0 ? false : craftingCanCraft,
  };
};

export const useCraftingTimeout = (
  characterData: CharacterSheetDefinition | null | undefined
): UseCraftingTimeoutDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const initialState = initializeTimeoutState(characterData);
  const [remainingSeconds, setRemainingSeconds] = useState(
    initialState.remainingSeconds
  );
  const [totalSeconds, setTotalSeconds] = useState(initialState.totalSeconds);
  const [canCraft, setCanCraft] = useState(initialState.canCraft);
  const intervalIdRef = useRef<number | null>(null);

  const clearCountdownInterval = () => {
    if (intervalIdRef.current === null) {
      return;
    }

    window.clearInterval(intervalIdRef.current);
    intervalIdRef.current = null;
  };

  const updateRemainingTime = () => {
    const nextRemainingSeconds = getRemainingSeconds();

    setRemainingSeconds(nextRemainingSeconds);

    if (nextRemainingSeconds > 0) {
      return;
    }

    craftingTimeoutEndsAt = null;
    craftingTimeoutDuration = 0;
    craftingCanCraft = true;
    craftingTimeoutResolved = true;
    setTotalSeconds(0);
    setCanCraft(true);
  };

  const startCountdownInterval = () => {
    clearCountdownInterval();
    intervalIdRef.current = window.setInterval(updateRemainingTime, 1000);
  };

  const requestCharacterTimers = async () => {
    const characterId = characterData?.id ?? 0;

    if (characterId === 0) {
      return;
    }

    const url = getUrl(CraftingApiUrls.UPDATE_CHARACTER_TIMERS, {
      character: characterId,
    });

    try {
      await apiHandler.get<unknown, never>(url);
    } catch {
      return;
    }
  };

  const handleTimeoutUpdate = (timeout: number | null) => {
    const nextTimeout = Math.max(timeout ?? 0, 0);
    const existingRemainingSeconds = getRemainingSeconds();

    if (nextTimeout === 0) {
      craftingTimeoutEndsAt = null;
      craftingTimeoutDuration = 0;
      craftingCanCraft = true;
      craftingTimeoutResolved = true;
      setRemainingSeconds(0);
      setTotalSeconds(0);
      setCanCraft(true);

      return;
    }

    craftingTimeoutEndsAt = Date.now() + nextTimeout * 1000;

    if (
      existingRemainingSeconds === 0 ||
      craftingTimeoutDuration < nextTimeout
    ) {
      craftingTimeoutDuration = nextTimeout;
    }

    craftingCanCraft = false;
    craftingTimeoutResolved = false;
    setRemainingSeconds(nextTimeout);
    setTotalSeconds(craftingTimeoutDuration);
    setCanCraft(false);
  };

  useCraftingTimeoutWebsocket({
    userId: characterData?.user_id ?? 0,
    onTimeoutUpdate: handleTimeoutUpdate,
  });

  useEffect(() => {
    void requestCharacterTimers();
    startCountdownInterval();

    return clearCountdownInterval;
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [characterData?.id]);

  const isTimeoutActive = remainingSeconds > 0;
  const characterPreventsCrafting =
    characterData?.can_craft === false && !craftingTimeoutResolved;
  const progress =
    isTimeoutActive && totalSeconds > 0
      ? Math.round((remainingSeconds / totalSeconds) * 100)
      : 0;

  return {
    isTimeoutActive,
    isCraftingDisabled:
      !canCraft || characterPreventsCrafting || isTimeoutActive,
    progress,
    formattedRemaining: formatCraftingTimeout(remainingSeconds),
  };
};
