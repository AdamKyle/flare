import { useEffect } from 'react';

import { useEchoInitializer } from './use-echo-initializer';
import { getUrl } from '../helpers/get-url';

export const useWebsocket = (
  url: string,
  params: Record<string, number>,
  type: 'private' | 'public',
  channelName: string
) => {
  const echoInitializer = useEchoInitializer();

  useEffect(() => {
    echoInitializer.echoInitialization.initialize();

    const echo = echoInitializer.echoInitialization.getEcho();

    const chanelUrl = getUrl(url, params);

    let channelListeningOn = null;

    if (type === 'private') {

      channelListeningOn = echo.private(chanelUrl);

      channelListeningOn.listen(channelName, (eventData: unknown) => {
        console.log('Here ... private', eventData);
      });
    }

    if (type === 'public') {
      channelListeningOn = echo.join(chanelUrl);

      channelListeningOn.listen(channelName, (eventData: unknown) => {
        console.log('Here ... public', eventData);
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
