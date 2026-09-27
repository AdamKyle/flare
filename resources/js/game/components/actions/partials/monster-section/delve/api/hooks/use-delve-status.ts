import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { resolveApiErrorMessage } from 'api-handler/utils/resolve-api-error-message';
import axios from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import UseDelveStatusDefinition from './definitions/use-delve-status-definition';
import UseDelveStatusParams from './definitions/use-delve-status-params';
import { DelveStatusDefinition } from '../definitions/delve-status-definition';
import DelveStatusUpdatedEventDefinition from '../definitions/delve-status-updated-event-definition';
import { DelveApiUrls } from '../enums/delve-api-urls';
import { DelveWebSocketChannels } from '../enums/delve-web-socket-channels';
import { DelveWebSocketEventNames } from '../enums/delve-web-socket-event-names';

import { ChannelType } from 'websockets/enums/channel-type';
import { useWebsocket } from 'websockets/hooks/use-websocket';

export const useDelveStatus = ({
  character_id: characterId,
  user_id: userId,
}: UseDelveStatusParams): UseDelveStatusDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [status, setStatus] = useState<DelveStatusDefinition | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [liveUpdateCount, setLiveUpdateCount] = useState(0);

  const abortControllerRef = useRef<AbortController | null>(null);

  const fetchStatus = useCallback(async (): Promise<void> => {
    if (characterId <= 0) {
      return;
    }

    abortControllerRef.current?.abort();
    const controller = new AbortController();
    abortControllerRef.current = controller;

    setLoading(true);
    setError(null);

    try {
      const result = await apiHandler.get<DelveStatusDefinition, never>(
        getUrl(DelveApiUrls.STATUS, { character: characterId }),
        { signal: controller.signal }
      );

      if (abortControllerRef.current !== controller) {
        return;
      }

      setStatus(result);
    } catch (requestError) {
      if (axios.isCancel(requestError)) {
        return;
      }

      if (abortControllerRef.current !== controller) {
        return;
      }

      setError(
        resolveApiErrorMessage(requestError, 'Unable to load Delve status.')
      );
    } finally {
      if (abortControllerRef.current === controller) {
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

  const handleStatusUpdated = useCallback(
    (payload: DelveStatusUpdatedEventDefinition) => {
      if (payload.user_id !== userId) {
        return;
      }

      setStatus(payload.status);
      setError(null);
      setLiveUpdateCount((currentCount) => currentCount + 1);
    },
    [userId]
  );

  useWebsocket<DelveStatusUpdatedEventDefinition>({
    url: DelveWebSocketChannels.STATUS_UPDATED,
    params: { userId },
    type: ChannelType.PRIVATE,
    channelName: DelveWebSocketEventNames.STATUS_UPDATED,
    onEvent: handleStatusUpdated,
    enabled: userId > 0,
  });

  const refetch = useCallback(() => {
    void fetchStatus();
  }, [fetchStatus]);

  return {
    status,
    loading,
    error,
    live_update_count: liveUpdateCount,
    refetch,
  };
};
