import { ChannelType } from '../../websocket-handler/enums/channel-type';
import { useWebsocket } from '../../websocket-handler/hooks/use-websocket';

import { CoreWebSocketChannels } from 'game-data/components/event-enums/core-web-socket-channels';
import { CoreWebSocketEventNames } from 'game-data/components/event-enums/core-web-socket-event-names';
import CharacterUpdateWireProps from 'game-data/components/types/character-update-wire-props';
import UseCharacterBoonsUpdateStreamResponse from 'game-data/hooks/definitions/use-character-boons-update-stream-response';
import UseCharterUpdateStreamResponse from 'game-data/hooks/definitions/use-character-update-stream-response';
import UseLocationBasedCraftingOptionsStreamResponse from 'game-data/hooks/definitions/use-location-based-crafting-options-stream-response';

export const CharacterUpdatesWire = ({
  userId,
  onEvent,
  onCraftingOptionsEvent,
  onBoonsEvent,
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

  useWebsocket<UseLocationBasedCraftingOptionsStreamResponse>({
    url: CoreWebSocketChannels.UPDATE_LOCATION_BASED_CRAFTING_OPTIONS,
    params: { userId },
    type: ChannelType.PRIVATE,
    channelName: CoreWebSocketEventNames.UPDATE_LOCATION_BASED_CRAFTING_OPTIONS,
    onEvent: onCraftingOptionsEvent,
  });

  useWebsocket<UseCharacterBoonsUpdateStreamResponse>({
    url: CoreWebSocketChannels.UPDATE_BOONS,
    params: { userId },
    type: ChannelType.PRIVATE,
    channelName: CoreWebSocketEventNames.UPDATE_BOONS,
    onEvent: onBoonsEvent,
  });

  return null;
};
