import { useCallback, useState } from 'react';

import { UseAnnouncementMessagesDefinition } from './definitions/use-announcement-messages-definition';
import { ChannelType } from '../../../../../websocket-handler/enums/channel-type';
import { useWebsocket } from '../../../../../websocket-handler/hooks/use-websocket';
import AnnouncementMessageDefinition from '../../../../api-definitions/chat/annoucement-message-definition';
import { ChatWebSocketChannels } from '../enums/chat-web-socket-channels';
import { ChatWebsocketEventNames } from '../enums/chat-websocket-event-names';

export const useAnnouncementMessages =
  (): UseAnnouncementMessagesDefinition => {
    const [announcementMessages, setAnnouncementMessages] = useState<
      AnnouncementMessageDefinition[]
    >([]);

    const handleAnnouncementEvent = useCallback(
      (event: AnnouncementMessageDefinition) => {
        setAnnouncementMessages((previous) =>
          [event, ...previous].slice(0, 500)
        );
      },

      []
    );

    useWebsocket<AnnouncementMessageDefinition>({
      url: ChatWebSocketChannels.ANNOUNCEMENTS,
      params: {},
      type: ChannelType.PRIVATE,
      channelName: ChatWebsocketEventNames.ANNOUNCEMENT,
      onEvent: handleAnnouncementEvent,
    });

    return { announcementMessages };
  };
