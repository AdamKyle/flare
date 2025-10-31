import ChatType from '../../../../api-definitions/chat/chat-message-definition';

export default interface UseChatActionsParamsDefinition {
  chatMessages: ChatType[];
  setRequestParams: (payload: { message: string }) => void;
}
