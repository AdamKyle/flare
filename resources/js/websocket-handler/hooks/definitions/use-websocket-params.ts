import { ChannelType } from '../types/channel-type';

export interface UseWebsocketParams {
  channel: string;
  params?: Record<string, number>;
  kind?: ChannelType;
}
