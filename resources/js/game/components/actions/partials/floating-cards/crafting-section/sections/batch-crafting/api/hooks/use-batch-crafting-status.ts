import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import { extractBatchCraftingApiError } from '../../utils/extract-batch-crafting-api-error';
import {
  mergeBatchCraftingStatusUpdate,
  mergeBatchCraftingStatusUpdates,
} from '../../utils/merge-batch-crafting-status-update';
import BatchCraftingStatusDefinition from '../definitions/batch-crafting-status-definition';
import BatchCraftingStatusUpdatedDefinition from '../definitions/batch-crafting-status-updated-definition';
import { BatchCraftingApiUrls } from '../enums/batch-crafting-api-urls';
import { BatchCraftingWebSocketChannels } from '../enums/batch-crafting-web-socket-channels';
import { BatchCraftingWebSocketEventNames } from '../enums/batch-crafting-web-socket-event-names';
import UseBatchCraftingStatusDefinition from './definitions/use-batch-crafting-status-definition';
import UseBatchCraftingStatusParams from './definitions/use-batch-crafting-status-params';

import { ChannelType } from 'websockets/enums/channel-type';
import { useWebsocket } from 'websockets/hooks/use-websocket';

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
  const [liveUpdateCount, setLiveUpdateCount] = useState(0);
  const abortControllerRef = useRef<AbortController | null>(null);
  const requestGenerationRef = useRef(0);
  const initialStatusResolvedRef = useRef(false);
  const pendingStatusUpdatesRef = useRef<
    BatchCraftingStatusUpdatedDefinition[]
  >([]);

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

      const nextStatus = mergeBatchCraftingStatusUpdates(
        result,
        pendingStatusUpdatesRef.current
      );

      pendingStatusUpdatesRef.current = [];
      initialStatusResolvedRef.current = true;

      setStatus(nextStatus);
    } catch (requestError) {
      if (axios.isCancel(requestError)) {
        return;
      }

      if (requestGenerationRef.current !== requestGeneration) {
        return;
      }

      if (pendingStatusUpdatesRef.current.length > 0) {
        initialStatusResolvedRef.current = true;
        pendingStatusUpdatesRef.current = [];

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
    initialStatusResolvedRef.current = false;
    pendingStatusUpdatesRef.current = [];

    void fetchStatus();

    return () => {
      abortControllerRef.current?.abort();
    };
  }, [fetchStatus]);

  const handleStatusUpdatedEvent = useCallback(
    (update: BatchCraftingStatusUpdatedDefinition) => {
      if (!initialStatusResolvedRef.current) {
        pendingStatusUpdatesRef.current = [
          ...pendingStatusUpdatesRef.current,
          update,
        ];
      }

      setStatus((currentStatus) =>
        mergeBatchCraftingStatusUpdate(currentStatus, update)
      );
      setError(null);
      setLoading(false);
      setLiveUpdateCount((currentCount) => currentCount + 1);
    },
    []
  );

  useWebsocket<BatchCraftingStatusUpdatedDefinition>({
    url: BatchCraftingWebSocketChannels.STATUS_UPDATED,
    params: { userId },
    type: ChannelType.PRIVATE,
    channelName: BatchCraftingWebSocketEventNames.STATUS_UPDATED,
    onEvent: handleStatusUpdatedEvent,
    enabled: userId > 0,
  });

  return {
    status,
    loading,
    error,
    live_update_count: liveUpdateCount,
    refresh_status: fetchStatus,
  };
};
