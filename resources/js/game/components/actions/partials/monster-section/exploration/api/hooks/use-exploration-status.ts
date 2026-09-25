import { useCallback, useMemo } from 'react';

import UseExplorationStatusDefinition from './definitions/use-exploration-status-definition';
import { useFetchExplorationOutput } from './use-fetch-exploration-output';

import { useGameData } from 'game-data/hooks/use-game-data';

/**
 * The Exploration websocket subscription is owned by the persistent
 * `GameDataProvider`, so it survives this panel unmounting. Once a live push
 * exists it is authoritative over the REST fetch; `refetch` only reloads the
 * REST value and never clears the newer websocket state.
 */
export const useExplorationStatus = (
  characterId: number
): UseExplorationStatusDefinition => {
  const {
    data,
    loading,
    error,
    refetch: refetchFromApi,
  } = useFetchExplorationOutput(characterId);
  const { gameData } = useGameData();

  const liveUpdate = gameData?.explorationOutput ?? null;

  const merged = useMemo(() => liveUpdate ?? data, [liveUpdate, data]);

  const refetch = useCallback(() => {
    refetchFromApi();
  }, [refetchFromApi]);

  return { data: merged, loading, error, refetch };
};
