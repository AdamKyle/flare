import { useCallback, useEffect, useRef } from 'react';

import { ChannelType } from '../../../websocket-handler/enums/channel-type';
import { useWebsocket } from '../../../websocket-handler/hooks/use-websocket';

interface DelveMonitoringUpdatedPayload {
  character_id: number;
}

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
    url: 'admin-monitoring-delve',
    params: {},
    type: ChannelType.PRIVATE,
    channelName: '.delve.monitoring.updated',
    onEvent: handleEvent,
  });

  useEffect(() => {
    return () => {
      clearTimeout(debounceTimer.current);
    };
  }, []);
}
