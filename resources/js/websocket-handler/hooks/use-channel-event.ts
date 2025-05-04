import { useWebsocket } from './use-websocket';

export function useChannelEvent(
  channel: string,
  params: Record<string, number>,
  kind: 'private' | 'public',
  eventName: string
) {
  useWebsocket(channel, params, kind, eventName);
}
