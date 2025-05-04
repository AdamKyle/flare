import { useChannelEvent } from '../../../../../websocket-handler/hooks/use-channel-event';
import { WebSocketChannels } from '../enums/web-socket-channels';
import UseMovementTimerParams from './definitions/use-movement-timer-params';
import { ChannelType } from '../../../../../websocket-handler/enums/channel-type';
import { WebsocketEventNames } from '../enums/websocket-event-names';

export const useMovementTimer = ({ characterData }: UseMovementTimerParams) => {
  useChannelEvent(
    WebSocketChannels.MOVEMENT_TIME_OUT,
    { userId: characterData?.user_id || 0 },
    ChannelType.PRIVATE,
    WebsocketEventNames.MOVEMENT_TIME_OUT
  );
};
