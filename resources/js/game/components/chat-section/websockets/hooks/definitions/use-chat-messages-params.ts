import BaseWebSocketParams from './base-web-socket-params';

export default interface UseChatMessagesParams extends BaseWebSocketParams {
  include_private_channels?: boolean;
}
