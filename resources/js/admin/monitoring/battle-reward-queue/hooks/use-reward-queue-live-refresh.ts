import { useCallback, useEffect, useRef } from 'react';

import { ChannelType } from '../../../../websocket-handler/enums/channel-type';
import { useWebsocket } from '../../../../websocket-handler/hooks/use-websocket';
import { RewardQueueWebsocketEvents } from '../enums/reward-queue-websocket-events';
import BattleRewardQueueUpdatedPayload from '../types/battle-reward-queue-updated-payload';

export default function useRewardQueueLiveRefresh(refresh: () => void) {
  const debounceTimer = useRef<ReturnType<typeof setTimeout> | undefined>(
    undefined
  );

  const handleEvent = useCallback(
    (_payload: BattleRewardQueueUpdatedPayload) => {
      clearTimeout(debounceTimer.current);
      debounceTimer.current = setTimeout(refresh, 500);
    },
    [refresh]
  );

  useWebsocket<BattleRewardQueueUpdatedPayload>({
    url: RewardQueueWebsocketEvents.CHANNEL,
    params: {},
    type: ChannelType.PRIVATE,
    channelName: RewardQueueWebsocketEvents.UPDATED,
    onEvent: handleEvent,
  });

  useEffect(() => {
    return () => {
      clearTimeout(debounceTimer.current);
    };
  }, []);
}
