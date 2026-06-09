import { useState } from 'react';

import UseCraftingIntroductionDefinition from './definitions/use-crafting-introduction-definition';

const STORAGE_KEY = 'tlessa.crafting.introduction_acknowledged';

const hasAcknowledgedIntroduction = (): boolean => {
  try {
    return window.localStorage.getItem(STORAGE_KEY) === 'true';
  } catch {
    return false;
  }
};

export const useCraftingIntroduction =
  (): UseCraftingIntroductionDefinition => {
    const [introductionAcknowledged, setIntroductionAcknowledged] =
      useState<boolean>(hasAcknowledgedIntroduction);

    const acknowledgeIntroduction = () => {
      try {
        window.localStorage.setItem(STORAGE_KEY, 'true');
      } catch {
        setIntroductionAcknowledged(true);

        return;
      }

      setIntroductionAcknowledged(true);
    };

    return {
      introductionAcknowledged,
      acknowledgeIntroduction,
    };
  };
