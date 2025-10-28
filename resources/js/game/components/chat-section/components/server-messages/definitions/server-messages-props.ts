import ServerMessagesDefinition from '../../../../../api-definitions/chat/server-messages-definition';

export default interface ServerMessagesProps {
  server_messages: ServerMessagesDefinition[];
  character_id: number;
  view_port: number;
  is_automation_running: boolean;
}
