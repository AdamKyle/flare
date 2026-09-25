import { ChannelType } from '../../websocket-handler/enums/channel-type';
import { useWebsocket } from '../../websocket-handler/hooks/use-websocket';

import { CoreWebSocketChannels } from 'game-data/components/event-enums/core-web-socket-channels';
import { CoreWebSocketEventNames } from 'game-data/components/event-enums/core-web-socket-event-names';
import BattleRewardProgressionUpdatesWireProps from 'game-data/components/types/battle-reward-progression-updates-wire-props';
import UseBattleRewardProgressionUpdateStreamResponse from 'game-data/hooks/definitions/use-battle-reward-progression-update-stream-response';

const BattleRewardProgressionUpdatesWire = ({
  userId,
  onEvent,
}: BattleRewardProgressionUpdatesWireProps) => {
  useWebsocket<UseBattleRewardProgressionUpdateStreamResponse>({
    url: CoreWebSocketChannels.BATTLE_REWARD_PROGRESSION,
    params: { userId },
    type: ChannelType.PRIVATE,
    channelName: CoreWebSocketEventNames.BATTLE_REWARD_PROGRESSION,
    onEvent,
  });

  return null;
};

export default BattleRewardProgressionUpdatesWire;
