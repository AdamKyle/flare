import { useCallback, useEffect, useRef } from 'react';

import { ChannelType } from '../../../../websocket-handler/enums/channel-type';
import { useWebsocket } from '../../../../websocket-handler/hooks/use-websocket';
import { ExplorationWebsocketEvents } from '../enums/exploration-websocket-events';
import ExplorationMonitoringUpdatedPayload from '../types/exploration-monitoring-updated-payload';

export default function useExplorationLiveRefresh(refresh: () => void) {
  const debounceTimer = useRef<ReturnType<typeof setTimeout> | undefined>(
    undefined
  );

  const handleEvent = useCallback(
    (_payload: ExplorationMonitoringUpdatedPayload) => {
      clearTimeout(debounceTimer.current);
      debounceTimer.current = setTimeout(refresh, 500);
    },
    [refresh]
  );

  useWebsocket<ExplorationMonitoringUpdatedPayload>({
    url: ExplorationWebsocketEvents.CHANNEL,
    params: {},
    type: ChannelType.PRIVATE,
    channelName: ExplorationWebsocketEvents.UPDATED,
    onEvent: handleEvent,
  });

  useEffect(() => {
    return () => {
      clearTimeout(debounceTimer.current);
    };
  }, []);
}
