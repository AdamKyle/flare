import ActiveAutomationDefinition from 'game-data/api-data-definitions/character/active-automation-definition';

interface CharacterStatusesDefinition {
  is_dead: boolean;
  can_attack: boolean;
  can_attack_again_at: number;
  is_automation_running: boolean;
  is_faction_loyalty_automation_running: boolean;
  is_delve_running: boolean;
  active_automation: ActiveAutomationDefinition | null;
  automation_completed_at: number;
}

export default interface UseCharacterStatusStreamResponse {
  characterStatuses: CharacterStatusesDefinition;
}
