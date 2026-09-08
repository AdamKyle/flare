import { useEffect, useRef } from 'react';

import { ChannelType } from '../enums/channel-type';
import { getUrl } from '../helpers/get-url';
import UseWebsocketParams from './definition/use-websocket-params';
import { useEchoInitializer } from './use-echo-initializer';

export const useWebsocket = <T>({
  url,
  params,
  type,
  channelName,
  onEvent,
  enabled = true,
}: UseWebsocketParams<T>) => {
  const { echoInitialization } = useEchoInitializer();

  const onEventRef = useRef(onEvent);
  onEventRef.current = onEvent;

  const listenerRef = useRef<(eventData: T) => void>((eventData) => {
    onEventRef.current(eventData);
  });

  const resolvedUrl = getUrl(url, params);

  useEffect(() => {
    if (!enabled) {
      return;
    }

    echoInitialization.initialize();

    const echo = echoInitialization.getEcho();

    const resolveChannel = () => {
      if (type === ChannelType.PRIVATE) {
        return echo.private(resolvedUrl);
      }

      if (type === ChannelType.PRESENCE) {
        return echo.join(resolvedUrl);
      }

      return echo.channel(resolvedUrl);
    };

    const channel = resolveChannel();

    channel.listen(channelName, listenerRef.current);

    return () => {
      echo.leave(resolvedUrl);
    };
  }, [enabled, resolvedUrl, channelName, type, echoInitialization]);
};
