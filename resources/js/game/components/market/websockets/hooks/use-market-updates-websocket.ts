import UseMarketUpdatesWebsocketParams from './definitions/use-market-updates-websocket-params';
import MarketUpdateEventDefinition from '../definitions/market-update-event-definition';
import { MarketWebsocketChannels } from '../enums/market-websocket-channels';
import { MarketWebsocketEventNames } from '../enums/market-websocket-event-names';

import { ChannelType } from 'websockets/enums/channel-type';
import { useWebsocket } from 'websockets/hooks/use-websocket';

export const useMarketUpdatesWebsocket = ({
  enabled,
  on_market_update,
}: UseMarketUpdatesWebsocketParams): void => {
  const handleMarketUpdate = (eventData: MarketUpdateEventDefinition) => {
    on_market_update(eventData.marketListings.data);
  };

  useWebsocket<MarketUpdateEventDefinition>({
    url: MarketWebsocketChannels.UPDATE_MARKET,
    params: {},
    type: ChannelType.PRESENCE,
    channelName: MarketWebsocketEventNames.UPDATE_MARKET,
    onEvent: handleMarketUpdate,
    enabled,
  });
};
