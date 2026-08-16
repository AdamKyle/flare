import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

import { BatchCraftingApiUrls } from '../enums/batch-crafting-api-urls';
import UseBatchCraftingActionsDefinition from './definitions/use-batch-crafting-actions-definition';
import { extractBatchCraftingApiError } from '../../utils/extract-batch-crafting-api-error';

export const useBatchCraftingActions = (
  characterId: number
): UseBatchCraftingActionsDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const [cancelling, setCancelling] = useState(false);
  const [dismissing, setDismissing] = useState(false);
  const [acknowledging, setAcknowledging] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const abortControllerRef = useRef<AbortController | null>(null);

  useEffect(() => {
    return () => {
      abortControllerRef.current?.abort();
    };
  }, []);

  const post = async (
    url: BatchCraftingApiUrls,
    fallbackMessage: string
  ): Promise<boolean> => {
    if (abortControllerRef.current !== null) {
      return false;
    }

    const controller = new AbortController();
    abortControllerRef.current = controller;

    setError(null);

    try {
      await apiHandler.post<{ message: string }, never, Record<string, never>>(
        getUrl(url, { character: characterId }),
        {},
        { signal: controller.signal }
      );

      return true;
    } catch (requestError) {
      if (axios.isCancel(requestError)) {
        return false;
      }

      setError(extractBatchCraftingApiError(requestError, fallbackMessage));

      return false;
    } finally {
      if (abortControllerRef.current === controller) {
        abortControllerRef.current = null;
      }
    }
  };

  const cancel = async (): Promise<boolean> => {
    if (cancelling) {
      return false;
    }

    setCancelling(true);

    try {
      return await post(
        BatchCraftingApiUrls.CANCEL,
        'Unable to cancel Batch Crafting.'
      );
    } finally {
      setCancelling(false);
    }
  };

  const dismiss = async (): Promise<boolean> => {
    if (dismissing) {
      return false;
    }

    setDismissing(true);

    try {
      return await post(
        BatchCraftingApiUrls.DISMISS,
        'Unable to dismiss Batch Crafting.'
      );
    } finally {
      setDismissing(false);
    }
  };

  const acknowledgeInfo = async (): Promise<boolean> => {
    if (acknowledging) {
      return false;
    }

    setAcknowledging(true);

    try {
      return await post(
        BatchCraftingApiUrls.ACKNOWLEDGE_INFO,
        'Unable to save your acknowledgement.'
      );
    } finally {
      setAcknowledging(false);
    }
  };

  return {
    cancelling,
    dismissing,
    acknowledging,
    error,
    cancel,
    dismiss,
    acknowledgeInfo,
  };
};
