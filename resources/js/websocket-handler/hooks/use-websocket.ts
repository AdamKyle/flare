import { useEffect, useRef, useState } from 'react';
import { match } from 'ts-pattern';

import { useEchoInitializer } from './use-echo-initializer';
import { getUrl } from '../helpers/get-url';
import ErrorChannelShapeDefinition from './definitions/error-channel-shape-definition';
import { UseWebsocketReturn } from './definitions/use-websocket-definition';
import { UseWebsocketParams } from './definitions/use-websocket-params';
import WebsocketErrorDefinition from './definitions/websocket-error-definition';
import { ChannelType } from './types/channel-type';

export const useWebsocket = <TError = unknown>({
  channel,
  params,
  kind = ChannelType.PRIVATE,
}: UseWebsocketParams): UseWebsocketReturn<TError> => {
  const { echoInitialization } = useEchoInitializer();
  echoInitialization.initialize();
  const echo = echoInitialization.getEcho();

  const channelRef = useRef<ErrorChannelShapeDefinition<TError> | null>(null);
  const [error, setError] = useState<WebsocketErrorDefinition<TError> | null>(
    null
  );
  const channelName = getUrl(channel, params);

  useEffect(() => {
    channelRef.current = match(kind)
      .with(ChannelType.PRIVATE, () => echo.private(channelName))
      .with(ChannelType.PRESENCE, () => echo.join(channelName))
      .with(ChannelType.PUBLIC, () => echo.channel(channelName))
      .exhaustive() as ErrorChannelShapeDefinition<TError>;

    const conn = echo.connector.pusher.connection;
    conn.bind('error', (e: TError) => {
      setError({ type: 'connection', info: e });
    });

    if (typeof channelRef.current.error === 'function') {
      channelRef.current.error((status: TError) => {
        setError({ type: 'subscription', info: status });
      });
    }

    return () => {
      channelRef.current!.unsubscribe();
      conn.unbind('error');
      channelRef.current = null;
    };
  }, [channelName, kind, echo]);

  const listen = <TPayload>(
    eventName: string,
    handler: (payload: TPayload) => void
  ): (() => void) => {
    if (!channelRef.current) return () => {};
    channelRef.current.listen(eventName, handler);
    return () => {
      channelRef.current!.stopListening(eventName, handler);
    };
  };

  return { listen, error };
};
