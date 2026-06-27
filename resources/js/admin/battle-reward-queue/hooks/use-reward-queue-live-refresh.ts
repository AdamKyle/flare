import { useCallback, useEffect, useRef } from 'react';

import { ChannelType } from '../../../websocket-handler/enums/channel-type';
import { useWebsocket } from '../../../websocket-handler/hooks/use-websocket';

interface BattleRewardQueueUpdatedPayload {
  character_id: number;
  change: string;
}

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
    url: 'admin-character-reward-queue',
    params: {},
    type: ChannelType.PRIVATE,
    channelName: '.battle.reward.queue.updated',
    onEvent: handleEvent,
  });

  useEffect(() => {
    return () => {
      clearTimeout(debounceTimer.current);
    };
  }, []);
}
