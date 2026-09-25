import { useContext } from 'react';

import { CurrencyType } from '../enums/currency-type';

import { GameDataContext } from 'game-data/game-data-context';

/**
 * Currency displays also render on Information pages, which have no
 * GameDataProvider, so the limit is read without requiring the provider.
 */
export const useCurrencyLimit = (currency: CurrencyType): number | null => {
  const gameDataContext = useContext(GameDataContext);

  return (
    gameDataContext?.gameData?.character?.currency_limits?.[currency] ?? null
  );
};
