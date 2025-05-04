import { useEffect, useState } from 'react'; // your channel‑name templates

import WebsocketErrorDefinition from './definitions/websocket-error-definition';
import { ChannelType } from './types/channel-type'; // public|private|presence
import { useWebsocket } from './use-websocket';

export type ChannelEventResult<TData, TError> = {
  data: TData | null;
  error: WebsocketErrorDefinition<TError> | null;
};

export function useChannelEvent<TData, TError = unknown>(
  channel: string,
  params: Record<string, number>,
  kind: ChannelType,
  eventName: string
): ChannelEventResult<TData, TError> {
  const { listen, error } = useWebsocket<TError>({ channel, params, kind });
  const [data, setData] = useState<TData | null>(null);

  useEffect(() => {
    return listen<TData>(eventName, (payload) => {
      setData(payload);
    });
  }, [listen, eventName]);

  return { data, error };
}
