import { useCallback, useEffect, useRef } from 'react';

import { ChannelType } from '../../../../websocket-handler/enums/channel-type';
import { useWebsocket } from '../../../../websocket-handler/hooks/use-websocket';
import { BatchCraftingWebsocketEvents } from '../enums/batch-crafting-websocket-events';
import BatchCraftingMonitoringUpdatedPayload from '../types/batch-crafting-monitoring-updated-payload';

export default function useBatchCraftingLiveRefresh(refresh: () => void) {
  const debounceTimer = useRef<ReturnType<typeof setTimeout> | undefined>(
    undefined
  );

  const handleEvent = useCallback(
    (_payload: BatchCraftingMonitoringUpdatedPayload) => {
      clearTimeout(debounceTimer.current);
      debounceTimer.current = setTimeout(refresh, 500);
    },
    [refresh]
  );

  useWebsocket<BatchCraftingMonitoringUpdatedPayload>({
    url: BatchCraftingWebsocketEvents.CHANNEL,
    params: {},
    type: ChannelType.PRIVATE,
    channelName: BatchCraftingWebsocketEvents.UPDATED,
    onEvent: handleEvent,
  });

  useEffect(() => {
    return () => {
      clearTimeout(debounceTimer.current);
    };
  }, []);
}
