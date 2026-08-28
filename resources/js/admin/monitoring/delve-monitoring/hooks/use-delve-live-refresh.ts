import { useCallback, useEffect, useRef } from 'react';

import { ChannelType } from '../../../../websocket-handler/enums/channel-type';
import { useWebsocket } from '../../../../websocket-handler/hooks/use-websocket';
import { DelveWebsocketEvents } from '../enums/delve-websocket-events';
import DelveMonitoringUpdatedPayload from '../types/delve-monitoring-updated-payload';

export default function useDelveMonitoringLiveRefresh(refresh: () => void) {
  const debounceTimer = useRef<ReturnType<typeof setTimeout> | undefined>(
    undefined
  );

  const handleEvent = useCallback(
    (_payload: DelveMonitoringUpdatedPayload) => {
      clearTimeout(debounceTimer.current);
      debounceTimer.current = setTimeout(refresh, 500);
    },
    [refresh]
  );

  useWebsocket<DelveMonitoringUpdatedPayload>({
    url: DelveWebsocketEvents.CHANNEL,
    params: {},
    type: ChannelType.PRIVATE,
    channelName: DelveWebsocketEvents.UPDATED,
    onEvent: handleEvent,
  });

  useEffect(() => {
    return () => {
      clearTimeout(debounceTimer.current);
    };
  }, []);
}
