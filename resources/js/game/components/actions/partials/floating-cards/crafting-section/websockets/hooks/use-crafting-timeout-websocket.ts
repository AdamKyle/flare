import { ChannelType } from '../../../../../../../../websocket-handler/enums/channel-type';
import { useWebsocket } from '../../../../../../../../websocket-handler/hooks/use-websocket';
import { WebSocketChannels } from '../enums/web-socket-channels';
import { WebsocketEventNames } from '../enums/websocket-event-names';
import CraftingTimeoutEventDefinition from '../event-data-definitions/crafting-timeout-event-definition';
import UseCraftingTimeoutWebsocketParams from './definitions/use-crafting-timeout-websocket-params';

export const useCraftingTimeoutWebsocket = ({
  userId,
  onTimeoutUpdate,
}: UseCraftingTimeoutWebsocketParams) => {
  const handleEventData = (data: CraftingTimeoutEventDefinition) => {
    onTimeoutUpdate(data.timeout);
  };

  useWebsocket({
    url: WebSocketChannels.CRAFTING_TIME_OUT,
    params: {
      userId,
    },
    type: ChannelType.PRIVATE,
    channelName: WebsocketEventNames.CRAFTING_TIME_OUT,
    onEvent: handleEventData,
    enabled: userId > 0,
  });
};
