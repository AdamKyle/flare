import ActiveAutomationDefinition from 'game-data/api-data-definitions/character/active-automation-definition';

export default interface FactionLoyaltyAutomationStatusProps {
  character_id: number;
  active_automation: ActiveAutomationDefinition;
}
