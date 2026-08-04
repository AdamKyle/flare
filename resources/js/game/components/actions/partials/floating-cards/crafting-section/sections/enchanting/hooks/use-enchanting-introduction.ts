import { useState } from 'react';

import UseEnchantingIntroductionDefinition from './definitions/use-enchanting-introduction-definition';

const STORAGE_KEY = 'hide-enchanting-help';

const hasAcknowledgedIntroductionPermanently = (): boolean => {
  try {
    return window.localStorage.getItem(STORAGE_KEY) === 'true';
  } catch {
    return false;
  }
};

export const useEnchantingIntroduction =
  (): UseEnchantingIntroductionDefinition => {
    const [introductionAcknowledged, setIntroductionAcknowledged] =
      useState<boolean>(hasAcknowledgedIntroductionPermanently);

    const acknowledgeIntroduction = (hidePermanently: boolean): void => {
      if (!hidePermanently) {
        setIntroductionAcknowledged(true);

        return;
      }

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
