import AnnouncementMessageDefinition from '../../game/api-definitions/chat/annoucement-message-definition';
import { ChatWebSocketChannels } from '../../game/components/chat-section/websockets/enums/chat-web-socket-channels';
import { ChatWebsocketEventNames } from '../../game/components/chat-section/websockets/enums/chat-websocket-event-names';
import { ChannelType } from '../../websocket-handler/enums/channel-type';
import { useWebsocket } from '../../websocket-handler/hooks/use-websocket';

import AnnouncementsUpdateWireProps from 'game-data/components/types/announcements-update-wire-props';

export const AnnouncementUpdatesWire = ({
  onEvent,
}: AnnouncementsUpdateWireProps) => {
  useWebsocket<AnnouncementMessageDefinition>({
    url: ChatWebSocketChannels.ANNOUNCEMENTS,
    params: {},
    type: ChannelType.PUBLIC,
    channelName: ChatWebsocketEventNames.ANNOUNCEMENT,
    onEvent: onEvent,
  });

  return null;
};
