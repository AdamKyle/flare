import { ExplorationWebSocketChannels } from '../../game/components/actions/partials/monster-section/exploration/api/enums/exploration-web-socket-channels';
import { ExplorationWebSocketEventNames } from '../../game/components/actions/partials/monster-section/exploration/api/enums/exploration-web-socket-event-names';
import ExplorationOutputResponseDefinition from '../../game/components/actions/partials/monster-section/exploration/types/exploration-output-response-definition';
import { ChannelType } from '../../websocket-handler/enums/channel-type';
import { useWebsocket } from '../../websocket-handler/hooks/use-websocket';

import ExplorationOutputUpdatesWireProps from 'game-data/components/types/exploration-output-updates-wire-props';

const ExplorationOutputUpdatesWire = ({
  userId,
  onEvent,
}: ExplorationOutputUpdatesWireProps) => {
  useWebsocket<ExplorationOutputResponseDefinition>({
    url: ExplorationWebSocketChannels.OUTPUT,
    params: { userId },
    type: ChannelType.PRIVATE,
    channelName: ExplorationWebSocketEventNames.OUTPUT,
    onEvent,
  });

  return null;
};

export default ExplorationOutputUpdatesWire;
