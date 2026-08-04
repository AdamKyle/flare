import { useMemo, useState } from 'react';

import UseLabyrinthOracleFlowDefinition from './definitions/use-labyrinth-oracle-flow-definition';
import { useLabyrinthOracleApi } from '../api/hooks/use-labyrinth-oracle-api';
import { useTransferItemAttributesApi } from '../api/hooks/use-transfer-item-attributes-api';
import { buildTransferItemAttributesRequest } from '../utils/build-transfer-item-attributes-request';

import { useGameData } from 'game-data/hooks/use-game-data';

export const useLabyrinthOracleFlow = (): UseLabyrinthOracleFlowDefinition => {
  const { gameData } = useGameData();

  const characterId = gameData?.character?.id ?? 0;

  const { data, loading, error, replaceData } = useLabyrinthOracleApi({
    characterId,
  });

  const [sourceId, setSourceId] = useState<number | null>(null);
  const [destinationId, setDestinationId] = useState<number | null>(null);
  const [status, setStatus] = useState<string | null>(null);

  const request = buildTransferItemAttributesRequest(sourceId, destinationId);

  const {
    submitting,
    error: mutationError,
    transfer,
  } = useTransferItemAttributesApi({ characterId, request });

  const sourceItem = useMemo(
    () => data?.inventory.find((item) => item.id === sourceId) ?? null,
    [data, sourceId]
  );

  const destinationItem = useMemo(
    () => data?.inventory.find((item) => item.id === destinationId) ?? null,
    [data, destinationId]
  );

  const isSameItemSelected =
    sourceId !== null && destinationId !== null && sourceId === destinationId;

  const hasEnoughItemsToTransfer = (data?.inventory.length ?? 0) >= 2;

  const canSubmit = request !== null && !isSameItemSelected && !submitting;

  const selectSource = (id: number): void => {
    if (id === destinationId) {
      return;
    }

    setSourceId(id);
    setStatus(null);
  };

  const selectDestination = (id: number): void => {
    if (id === sourceId) {
      return;
    }

    setDestinationId(id);
    setStatus(null);
  };

  const submitTransfer = async (): Promise<void> => {
    const response = await transfer();

    if (!response) {
      return;
    }

    replaceData(response);
    setStatus(response.message ?? 'The attributes were transferred.');
    setSourceId(null);
    setDestinationId(null);
  };

  return {
    characterId,
    data,
    loading,
    error,
    mutationError,
    status,
    sourceId,
    destinationId,
    sourceItem,
    destinationItem,
    hasEnoughItemsToTransfer,
    submitting,
    canSubmit,
    selectSource,
    selectDestination,
    submitTransfer,
  };
};
