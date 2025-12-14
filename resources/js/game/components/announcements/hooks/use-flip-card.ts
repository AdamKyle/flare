import { useState } from 'react';

import UseFlipCardDefinition from './definitions/use-flip-card-definition';

export const useFlipCard = (): UseFlipCardDefinition => {
  const [flippedCardKey, setFlippedCardKey] = useState<string | null>(null);

  const handleToggleCard = (cardKey: string) => {
    if (flippedCardKey === cardKey) {
      setFlippedCardKey(null);

      return;
    }

    setFlippedCardKey(cardKey);
  };

  return {
    flippedCardKey,
    handleToggleCard,
  };
};
