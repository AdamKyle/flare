import { ChannelType } from '../../enums/channel-type';

export default interface UseWebsocketParams<T> {
  url: string;
  params: Record<string, number>;
  type: ChannelType;
  channelName: string;
  onEvent: (data: T) => void;
}
