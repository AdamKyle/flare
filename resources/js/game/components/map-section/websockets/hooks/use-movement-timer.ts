import { WebSocketChannels } from '../enums/web-socket-channels';
import UseMovementTimerParams from './definitions/use-movement-timer-params';
import { ChannelType } from '../../../../../websocket-handler/enums/channel-type';
import { useWebsocket } from '../../../../../websocket-handler/hooks/use-websocket';
import { useFetchMovementTimeoutData } from '../../../actions/partials/floating-cards/map-section/hooks/use-fetch-movement-timeout-data';
import { WebsocketEventNames } from '../enums/websocket-event-names';
import MapMovementEventTimeoutDefinition from '../event-data-definitions/map-movement-event-timeout-definition';

export const useMovementTimer = ({ characterData }: UseMovementTimerParams) => {
  const { handleEventData: onDataUpdate } = useFetchMovementTimeoutData();

  const handleEventData = (data: MapMovementEventTimeoutDefinition) => {
    onDataUpdate(data);
  };

  useWebsocket({
    url: WebSocketChannels.MOVEMENT_TIME_OUT,
    params: {
      userId: characterData?.user_id || 0,
    },
    type: ChannelType.PRIVATE,
    channelName: WebsocketEventNames.MOVEMENT_TIME_OUT,
    onEvent: handleEventData,
  });
};
