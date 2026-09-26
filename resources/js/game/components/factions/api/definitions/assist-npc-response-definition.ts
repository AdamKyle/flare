import FactionLoyaltyDefinition from './faction-loyalty-definition';

export default interface AssistNpcResponseDefinition {
  message: string;
  faction_loyalty: FactionLoyaltyDefinition;
}
