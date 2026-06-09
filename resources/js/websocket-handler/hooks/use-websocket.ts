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
  const echoInitializer = useEchoInitializer();

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

    echoInitializer.echoInitialization.initialize();

    const echo = echoInitializer.echoInitialization.getEcho();

    let channel = null;

    if (type === ChannelType.PRIVATE) {
      channel = echo.private(resolvedUrl);
      channel.listen(channelName, listenerRef.current);
    }

    if (type === ChannelType.PUBLIC) {
      channel = echo.join(resolvedUrl);
      channel.listen(channelName, listenerRef.current);
    }

    return () => {
      if (!channel) {
        return;
      }

      channel.unsubscribe();
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [enabled, resolvedUrl, channelName, type]);
};
