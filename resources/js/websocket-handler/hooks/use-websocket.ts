import { useEffect } from 'react';

import { useEchoInitializer } from './use-echo-initializer';
import { ChannelType } from '../enums/channel-type';
import { getUrl } from '../helpers/get-url';
import UseWebsocketParams from './definition/use-websocket-params';

export const useWebsocket = <T>({
  url,
  params,
  type,
  channelName,
  onEvent,
}: UseWebsocketParams<T>) => {
  const echoInitializer = useEchoInitializer();

  useEffect(() => {
    echoInitializer.echoInitialization.initialize();

    const echo = echoInitializer.echoInitialization.getEcho();

    const chanelUrl = getUrl(url, params);

    let channelListeningOn = null;

    if (type === ChannelType.PRIVATE) {
      channelListeningOn = echo.private(chanelUrl);

      channelListeningOn.listen(channelName, (eventData: T) => {
        onEvent(eventData);
      });
    }

    if (type === ChannelType.PUBLIC) {
      channelListeningOn = echo.join(chanelUrl);

      channelListeningOn.listen(channelName, (eventData: T) => {
        onEvent(eventData);
      });
    }

    return () => {
      if (!channelListeningOn) {
        return;
      }

      channelListeningOn.unsubscribe();
    };
  }, [url]);
};
