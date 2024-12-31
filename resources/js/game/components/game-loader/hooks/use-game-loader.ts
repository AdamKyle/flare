import { useEffect } from 'react';

import UseGameLoaderDefinition from './definitions/use-game-loader-definition';
import { BatchApiCallKey } from './enums/batch-api-call-key';
import { useBatchApiCalls } from './use-batch-api-calls';
import { GameLoaderApiUrls } from '../api/enums/game-loader-api-urls';
import { useCharacterSheetApi } from '../api/hooks/use-character-sheet-api';
import { useMonsterApi } from '../api/hooks/use-monster-api';

export const useGameLoader = (): UseGameLoaderDefinition => {
  const { fetchCharacterData } = useCharacterSheetApi({
    url: GameLoaderApiUrls.CHARACTER_SHEET,
    urlParams: {
      character: 1,
    },
  });

  const { fetchMonstersData } = useMonsterApi({
    url: GameLoaderApiUrls.MONSTERS,
    urlParams: {
      character: 1,
    },
  });

  const { loading, progress, error, data, executeBatchApiCalls } =
    useBatchApiCalls([
      {
        api_call: fetchCharacterData,
        key: BatchApiCallKey.CHARACTER,
        progress_step: 50,
      },
      {
        api_call: fetchMonstersData,
        key: BatchApiCallKey.MONSTERS,
        progress_step: 50,
      },
    ]);

  useEffect(() => {
    if (loading) {
      executeBatchApiCalls();
    }
  }, [executeBatchApiCalls, loading]);

  return {
    loading,
    progress,
    error,
    data,
  };
};
