import ExplorationMessageDefinition from '../../../../../../api-definitions/chat/exploration-message-definition';

export default interface BeginExplorationResponseDefinition {
  message: string;
  exploration_message: ExplorationMessageDefinition;
}
