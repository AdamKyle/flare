import UseSlotTimeoutWebsocketParams from './definitions/use-slot-timeout-websocket-params';
import SlotTimeoutEventDefinition from '../definitions/slot-timeout-event-definition';
import { SlotWebsocketChannels } from '../enums/slot-websocket-channels';
import { SlotWebsocketEventNames } from '../enums/slot-websocket-event-names';

import { ChannelType } from 'websockets/enums/channel-type';
import { useWebsocket } from 'websockets/hooks/use-websocket';

export const useSlotTimeoutWebsocket = ({
  user_id,
  enabled,
  on_timeout_update,
}: UseSlotTimeoutWebsocketParams): void => {
  const handleTimeoutEvent = (eventData: SlotTimeoutEventDefinition) => {
    on_timeout_update(eventData.timeoutFor);
  };

  useWebsocket<SlotTimeoutEventDefinition>({
    url: SlotWebsocketChannels.SLOT_TIMEOUT,
    params: { userId: user_id },
    type: ChannelType.PRIVATE,
    channelName: SlotWebsocketEventNames.SLOT_TIMEOUT,
    onEvent: handleTimeoutEvent,
    enabled: enabled && user_id > 0,
  });
};
