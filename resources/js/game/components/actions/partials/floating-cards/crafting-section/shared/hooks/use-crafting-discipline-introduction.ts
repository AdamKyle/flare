import { useState } from 'react';

import UseCraftingDisciplineIntroductionDefinition from './definitions/use-crafting-discipline-introduction-definition';

const hasAcknowledgedIntroduction = (storageKey: string): boolean => {
  try {
    return window.localStorage.getItem(storageKey) === 'true';
  } catch {
    return false;
  }
};

export const useCraftingDisciplineIntroduction = (
  storageKey: string
): UseCraftingDisciplineIntroductionDefinition => {
  const [introductionAcknowledged, setIntroductionAcknowledged] =
    useState<boolean>(() => hasAcknowledgedIntroduction(storageKey));

  const acknowledgeIntroduction = (): void => {
    try {
      window.localStorage.setItem(storageKey, 'true');
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
