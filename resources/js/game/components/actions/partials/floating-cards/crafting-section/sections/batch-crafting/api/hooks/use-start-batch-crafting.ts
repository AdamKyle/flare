import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

import BatchCraftingStartRequestDefinition from '../definitions/batch-crafting-start-request-definition';
import BatchCraftingStartResponseDefinition from '../definitions/batch-crafting-start-response-definition';
import { BatchCraftingApiUrls } from '../enums/batch-crafting-api-urls';
import UseStartBatchCraftingDefinition from './definitions/use-start-batch-crafting-definition';
import { extractBatchCraftingApiError } from '../../utils/extract-batch-crafting-api-error';

export const useStartBatchCrafting = (
  characterId: number
): UseStartBatchCraftingDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const [starting, setStarting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const abortControllerRef = useRef<AbortController | null>(null);

  useEffect(() => {
    return () => {
      abortControllerRef.current?.abort();
    };
  }, []);

  const start = async (
    request: BatchCraftingStartRequestDefinition
  ): Promise<boolean> => {
    if (starting || abortControllerRef.current !== null) {
      return false;
    }

    const controller = new AbortController();
    abortControllerRef.current = controller;

    setStarting(true);
    setError(null);

    try {
      await apiHandler.post<
        BatchCraftingStartResponseDefinition,
        never,
        BatchCraftingStartRequestDefinition
      >(
        getUrl(BatchCraftingApiUrls.START, { character: characterId }),
        request,
        { signal: controller.signal }
      );

      return true;
    } catch (requestError) {
      if (axios.isCancel(requestError)) {
        return false;
      }

      setError(
        extractBatchCraftingApiError(
          requestError,
          'Unable to start Batch Crafting.'
        )
      );

      return false;
    } finally {
      if (abortControllerRef.current === controller) {
        abortControllerRef.current = null;
      }

      setStarting(false);
    }
  };

  return { starting, error, start };
};
