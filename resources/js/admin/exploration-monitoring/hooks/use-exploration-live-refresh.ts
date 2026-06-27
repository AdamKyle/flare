import { useCallback, useEffect, useRef } from 'react';

import { ChannelType } from '../../../websocket-handler/enums/channel-type';
import { useWebsocket } from '../../../websocket-handler/hooks/use-websocket';

interface ExplorationMonitoringUpdatedPayload {
  character_id: number;
}

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
    url: 'admin-monitoring-exploration',
    params: {},
    type: ChannelType.PRIVATE,
    channelName: '.exploration.monitoring.updated',
    onEvent: handleEvent,
  });

  useEffect(() => {
    return () => {
      clearTimeout(debounceTimer.current);
    };
  }, []);
}
