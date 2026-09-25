import ActiveAutomationDefinition from 'game-data/api-data-definitions/character/active-automation-definition';

export default interface MonsterExplorationConfigurationProps {
  character_id: number;
  selected_monster_id: number | null;
  active_automation: ActiveAutomationDefinition | null;
  on_close: () => void;
  on_started: () => void;
}
