import { ChannelType } from '../../websocket-handler/enums/channel-type';
import { useWebsocket } from '../../websocket-handler/hooks/use-websocket';

import { CoreWebSocketChannels } from 'game-data/components/event-enums/core-web-socket-channels';
import { CoreWebSocketEventNames } from 'game-data/components/event-enums/core-web-socket-event-names';
import MonsterUpdatesWireProps from 'game-data/components/types/monster-updates-wire-props';
import UseMonsterUpdateStreamResponse from 'game-data/hooks/definitions/use-monster-update-stream-response';

const MonsterUpdatesWire = ({ userId, onEvent }: MonsterUpdatesWireProps) => {
  useWebsocket<UseMonsterUpdateStreamResponse>({
    url: CoreWebSocketChannels.MONSTER_LIST,
    params: { userId },
    type: ChannelType.PRIVATE,
    channelName: CoreWebSocketEventNames.UPDATE_MONSTERS,
    onEvent,
  });

  return null;
};

export default MonsterUpdatesWire;
