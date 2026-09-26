import ActiveAutomationDefinition from 'game-data/api-data-definitions/character/active-automation-definition';

export default interface DelveConfigurationProps {
  character_id: number;
  can_set_pack_size: boolean;
  active_automation: ActiveAutomationDefinition | null;
  on_close: () => void;
  on_started: () => void;
}
