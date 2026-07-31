import { useCallback, useEffect, useRef } from 'react';

import { ChannelType } from '../../../websocket-handler/enums/channel-type';
import { useWebsocket } from '../../../websocket-handler/hooks/use-websocket';
import { FactionLoyaltyWebsocketEvents } from '../enums/faction-loyalty-websocket-events';
import FactionLoyaltyMonitoringUpdatedPayload from '../types/faction-loyalty-monitoring-updated-payload';

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
    url: FactionLoyaltyWebsocketEvents.CHANNEL,
    params: {},
    type: ChannelType.PRIVATE,
    channelName: FactionLoyaltyWebsocketEvents.UPDATED,
    onEvent: handleEvent,
  });

  useEffect(() => {
    return () => {
      clearTimeout(debounceTimer.current);
    };
  }, []);
}
