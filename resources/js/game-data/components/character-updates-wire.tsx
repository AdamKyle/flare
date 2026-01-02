import { ChannelType } from '../../websocket-handler/enums/channel-type';
import { useWebsocket } from '../../websocket-handler/hooks/use-websocket';

import { CoreWebSocketChannels } from 'game-data/components/event-enums/core-web-socket-channels';
import { CoreWebSocketEventNames } from 'game-data/components/event-enums/core-web-socket-event-names';
import CharacterUpdateWireProps from 'game-data/components/types/character-update-wire-props';
import UseCharterUpdateStreamResponse from 'game-data/hooks/definitions/use-character-update-stream-response';

export const CharacterUpdatesWire = ({
  userId,
  onEvent,
}: CharacterUpdateWireProps) => {
  useWebsocket<UseCharterUpdateStreamResponse>({
    url: CoreWebSocketChannels.UPDATE_CHARACTER,
    params: { userId },
    type: ChannelType.PRIVATE,
    channelName: CoreWebSocketEventNames.UPDATE_CHARACTER,
    onEvent,
  });

  useWebsocket<UseCharterUpdateStreamResponse>({
    url: CoreWebSocketChannels.UPDATE_CORE_CHARACTER_DETAILS,
    params: { userId },
    type: ChannelType.PRIVATE,
    channelName: CoreWebSocketEventNames.UPDATE_CORE_CHARACTER_DETAILS,
    onEvent,
  });

  return null;
};
