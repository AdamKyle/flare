import UseActiveBoonsWebsocketParams from './definitions/use-active-boons-websocket-params';
import { ActiveBoonsWebSocketChannels } from '../enums/active-boons-web-socket-channels';
import { ActiveBoonsWebsocketEventNames } from '../enums/active-boons-websocket-event-names';

import { ChannelType } from 'websockets/enums/channel-type';
import { useWebsocket } from 'websockets/hooks/use-websocket';

interface CharacterBoonsUpdateEventPayload {
  boons: unknown[];
}

export const useActiveBoonsWebsocket = ({
  userId,
  onBoonsUpdated,
}: UseActiveBoonsWebsocketParams): void => {
  useWebsocket<CharacterBoonsUpdateEventPayload>({
    url: ActiveBoonsWebSocketChannels.UPDATE_BOONS,
    params: { userId },
    type: ChannelType.PRIVATE,
    channelName: ActiveBoonsWebsocketEventNames.CHARACTER_BOONS_UPDATE,
    // The broadcast payload only carries raw boon rows, not the presentation
    // shape (boon_applied/amount_left), so refetch instead of trusting it.
    onEvent: () => onBoonsUpdated(),
    enabled: userId > 0,
  });
};
