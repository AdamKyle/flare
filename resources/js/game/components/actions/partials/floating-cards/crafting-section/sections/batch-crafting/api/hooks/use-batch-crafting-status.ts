import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import { extractBatchCraftingApiError } from '../../utils/extract-batch-crafting-api-error';
import BatchCraftingStatusDefinition from '../definitions/batch-crafting-status-definition';
import { BatchCraftingApiUrls } from '../enums/batch-crafting-api-urls';
import UseBatchCraftingStatusDefinition from './definitions/use-batch-crafting-status-definition';
import UseBatchCraftingStatusParams from './definitions/use-batch-crafting-status-params';
import { ChannelType } from '../../../../../../../../../../websocket-handler/enums/channel-type';
import { useWebsocket } from '../../../../../../../../../../websocket-handler/hooks/use-websocket';

const BATCH_CRAFTING_STATUS_UPDATED_CHANNEL =
  'batch-crafting-status-updated-{userId}';
const BATCH_CRAFTING_STATUS_UPDATED_EVENT = '.batch-crafting.status.updated';

export const useBatchCraftingStatus = ({
  characterId,
  userId,
}: UseBatchCraftingStatusParams): UseBatchCraftingStatusDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const [status, setStatus] = useState<BatchCraftingStatusDefinition | null>(
    null
  );
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const abortControllerRef = useRef<AbortController | null>(null);
  const requestGenerationRef = useRef(0);

  const fetchStatus = useCallback(async () => {
    if (characterId <= 0) {
      return;
    }

    abortControllerRef.current?.abort();
    const controller = new AbortController();
    abortControllerRef.current = controller;
    const requestGeneration = ++requestGenerationRef.current;

    setLoading(true);
    setError(null);

    try {
      const result = await apiHandler.get<BatchCraftingStatusDefinition, never>(
        getUrl(BatchCraftingApiUrls.STATUS, { character: characterId }),
        { signal: controller.signal }
      );

      if (requestGenerationRef.current !== requestGeneration) {
        return;
      }

      setStatus(result);
    } catch (requestError) {
      if (axios.isCancel(requestError)) {
        return;
      }

      if (requestGenerationRef.current !== requestGeneration) {
        return;
      }

      setError(
        extractBatchCraftingApiError(
          requestError,
          'Unable to load Batch Crafting status.'
        )
      );
    } finally {
      if (
        requestGenerationRef.current === requestGeneration &&
        abortControllerRef.current === controller
      ) {
        abortControllerRef.current = null;
        setLoading(false);
      }
    }
  }, [apiHandler, getUrl, characterId]);

  useEffect(() => {
    void fetchStatus();

    return () => {
      abortControllerRef.current?.abort();
    };
  }, [fetchStatus]);

  useWebsocket({
    url: BATCH_CRAFTING_STATUS_UPDATED_CHANNEL,
    params: { userId },
    type: ChannelType.PRIVATE,
    channelName: BATCH_CRAFTING_STATUS_UPDATED_EVENT,
    onEvent: () => void fetchStatus(),
    enabled: userId > 0,
  });

  return { status, loading, error, refetch: fetchStatus };
};
