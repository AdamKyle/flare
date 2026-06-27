import { useCallback, useEffect, useRef } from 'react';

import { ChannelType } from '../../../websocket-handler/enums/channel-type';
import { useWebsocket } from '../../../websocket-handler/hooks/use-websocket';

interface FactionLoyaltyMonitoringUpdatedPayload {
  character_id: number;
}

export default function useFactionLoyaltyLiveRefresh(refresh: () => void) {
  const debounceTimer = useRef<ReturnType<typeof setTimeout> | undefined>(
    undefined
  );

  const handleEvent = useCallback(
    (_payload: FactionLoyaltyMonitoringUpdatedPayload) => {
      clearTimeout(debounceTimer.current);
      debounceTimer.current = setTimeout(refresh, 500);
    },
    [refresh]
  );

  useWebsocket<FactionLoyaltyMonitoringUpdatedPayload>({
    url: 'admin-monitoring-faction-loyalty',
    params: {},
    type: ChannelType.PRIVATE,
    channelName: '.faction.loyalty.monitoring.updated',
    onEvent: handleEvent,
  });

  useEffect(() => {
    return () => {
      clearTimeout(debounceTimer.current);
    };
  }, []);
}
